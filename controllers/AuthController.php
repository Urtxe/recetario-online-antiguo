<?php
require_once 'services/AuthService.php';
require_once 'config/helpers.php';

class AuthController {
    private $authService;

    public function __construct($authService = null) {
        $this->authService = $authService ?: new AuthService();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_csrf_token($_POST['_csrf_token'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $resultado = $this->authService->login($email, $password, $_SERVER['REMOTE_ADDR'] ?? 'unknown');

            if ($resultado['success']) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $resultado['user']['id'];
                $_SESSION['usuario_nombre'] = $resultado['user']['nombre'];
                $_SESSION['usuario_email'] = $resultado['user']['email'];
                $_SESSION['usuario_rol'] = $resultado['user']['rol'] ?? 'usuario';
                header('Location: index.php');
                exit();
            } elseif (!empty($resultado['requires_2fa'])) {
                $_SESSION['pending_2fa_user'] = $resultado['user'];
                $error_2fa = $resultado['error'];
            } else {
                $error_login = $resultado['error'];
            }
        }
        require_once 'views/auth/login.php';
    }

    public function registro() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_csrf_token($_POST['_csrf_token'] ?? '');
            $usuario = $_POST['usuario'];
            $email = $_POST['email'];
            $pass = $_POST['password'];
            $passConfirm = $_POST['password-confirm'];

            $resultado = $this->authService->register($usuario, $email, $pass, $passConfirm);

            if ($resultado['success']) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $resultado['user']['id'];
                $_SESSION['usuario_nombre'] = $resultado['user']['nombre'];
                $_SESSION['usuario_email'] = $resultado['user']['email'];
                $_SESSION['usuario_rol'] = $resultado['user']['rol'] ?? 'usuario';
                header('Location: index.php');
                exit();
            } else {
                $error_registro = $resultado['error'];
            }
        }
        require_once 'views/auth/login.php';
    }

    public function logout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método no permitido');
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], app_is_https(), true);
        }
        session_destroy();
        header('Location: index.php?controller=auth&action=login');
        exit();
    }

    public function verificar2fa() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');

        $user = $_SESSION['pending_2fa_user'] ?? null;
        $code = trim($_POST['codigo_2fa'] ?? '');
        if (!$user) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        $resultado = $this->authService->verifyAdminTwoFactor($user, $code);
        if (!$resultado['success']) {
            $error_2fa = $resultado['error'];
            require_once 'views/auth/login.php';
            return;
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['usuario_email'] = $user['email'];
        $_SESSION['usuario_rol'] = $user['rol'] ?? 'usuario';
        unset($_SESSION['pending_2fa_user']);
        header('Location: index.php');
        exit();
    }

    public function perfil() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        $resultado = $this->authService->getProfileSummary($_SESSION['usuario_id']);
        if (!$resultado['success']) {
            $_SESSION['mensaje_flash'] = $resultado['error'];
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php');
            exit();
        }

        $usuario = $resultado['usuario'];
        $misRecetas = $resultado['misRecetas'];
        $misFavoritos = $resultado['misFavoritos'];
        $twoFactorSetupSecret = $_SESSION['two_factor_setup_secret'] ?? null;
        unset($_SESSION['two_factor_setup_secret']);

        require_once 'views/layouts/header.php';
        require_once 'views/auth/perfil.php';
        require_once 'views/layouts/footer.php';
    }

    public function actualizarPerfil() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');

        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $passwordActual = $_POST['password_actual'] ?? '';
        $passwordNueva = $_POST['password_nueva'] ?? '';
        $passwordConfirmar = $_POST['password_confirmar'] ?? '';

        $resultado = $this->authService->updateProfile(
            $_SESSION['usuario_id'],
            $nombre,
            $email,
            $passwordActual,
            $passwordNueva,
            $passwordConfirmar
        );

        if (!$resultado['success']) {
            $_SESSION['mensaje_flash'] = $resultado['error'];
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        $_SESSION['usuario_nombre'] = $resultado['usuario']['nombre'];
        $_SESSION['usuario_email'] = $resultado['usuario']['email'];
        $_SESSION['mensaje_flash'] = 'Perfil actualizado correctamente';
        $_SESSION['tipo_flash'] = 'success';
        header('Location: index.php?controller=auth&action=perfil');
        exit();
    }

    public function activar2fa() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');

        $usuario = (new Usuario())->obtenerPorIdConSeguridad($_SESSION['usuario_id']);
        if (!$usuario || !is_admin_user($usuario)) {
            http_response_code(403);
            exit('Acceso denegado');
        }

        $secret = $usuario['two_factor_secret'] ?? '';
        if (empty($secret)) {
            $secret = generate_totp_secret();
        }

        $codigo = trim($_POST['codigo_2fa'] ?? '');
        if ($codigo === '') {
            $resultado = (new Usuario())->activarDosFactores($usuario['id'], $secret);
            if ($resultado) {
                $_SESSION['two_factor_setup_secret'] = $secret;
                $_SESSION['mensaje_flash'] = 'Se ha generado tu secreto 2FA. Verifícalo ahora con tu app de autenticación.';
                $_SESSION['tipo_flash'] = 'success';
            } else {
                $_SESSION['mensaje_flash'] = 'No se pudo preparar el 2FA';
                $_SESSION['tipo_flash'] = 'error';
            }
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        if (!verify_totp_code($secret, $codigo)) {
            $_SESSION['mensaje_flash'] = 'Código 2FA inválido';
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        $modelo = new Usuario();
        if ($modelo->activarDosFactores($usuario['id'], $secret) && $modelo->confirmarDosFactores($usuario['id'])) {
            unset($_SESSION['two_factor_setup_secret']);
            $_SESSION['mensaje_flash'] = '2FA activado correctamente';
            $_SESSION['tipo_flash'] = 'success';
        } else {
            $_SESSION['mensaje_flash'] = 'No se pudo activar 2FA';
            $_SESSION['tipo_flash'] = 'error';
        }

        header('Location: index.php?controller=auth&action=perfil');
        exit();
    }

    public function desactivar2fa() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');
        $passwordActual = $_POST['password_actual'] ?? '';
        $usuario = (new Usuario())->obtenerPorIdConSeguridad($_SESSION['usuario_id']);
        if (!$usuario || empty($usuario['password']) || !password_verify($passwordActual, $usuario['password'])) {
            $_SESSION['mensaje_flash'] = 'La contraseña actual no es correcta';
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        $modelo = new Usuario();
        if ($modelo->desactivarDosFactores($_SESSION['usuario_id'])) {
            unset($_SESSION['two_factor_setup_secret']);
            $_SESSION['mensaje_flash'] = '2FA desactivado';
            $_SESSION['tipo_flash'] = 'success';
        } else {
            $_SESSION['mensaje_flash'] = 'No se pudo desactivar 2FA';
            $_SESSION['tipo_flash'] = 'error';
        }
        header('Location: index.php?controller=auth&action=perfil');
        exit();
    }

    public function solicitarRecuperacion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $resultado = $this->authService->startPasswordReset($email);

        if (!empty($resultado['token']) && app_env('APP_ENV', 'local') === 'local') {
            $_SESSION['mensaje_flash'] = 'Token de recuperación generado: ' . $resultado['token'];
        } else {
            $_SESSION['mensaje_flash'] = $resultado['message'];
        }

        $_SESSION['tipo_flash'] = 'success';
        header('Location: index.php?controller=auth&action=login');
        exit();
    }

    public function resetearPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');
        $token = trim($_POST['reset_token'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $resultado = $this->authService->completePasswordReset($token, $password, $passwordConfirm);
        if (!$resultado['success']) {
            $_SESSION['mensaje_flash'] = $resultado['error'];
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        $_SESSION['mensaje_flash'] = 'Contraseña actualizada. Ya puedes iniciar sesión.';
        $_SESSION['tipo_flash'] = 'success';
        header('Location: index.php?controller=auth&action=login');
        exit();
    }
}
