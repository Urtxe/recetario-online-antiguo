<?php
require_once 'models/Usuario.php';
require_once 'models/Receta.php';
require_once 'config/helpers.php';

class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_csrf_token($_POST['_csrf_token'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $limiterKey = 'login|' . strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            list($puedeIntentar, $intentos, $retryAfter) = rate_limit_check($limiterKey);

            if (!$puedeIntentar) {
                $error_login = 'Demasiados intentos. Prueba de nuevo en ' . max(1, (int)ceil($retryAfter / 60)) . ' minuto(s).';
                require_once 'views/auth/login.php';
                return;
            }
            
            $usuarioModel = new Usuario();
            $user = $usuarioModel->login($email, $password);
            
            if ($user) {
                rate_limit_clear($limiterKey);
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                header('Location: index.php');
                exit();
            } else {
                rate_limit_register_failure($limiterKey);
                $error_login = 'Usuario o contraseña incorrectos';
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

            $modelo = new Usuario();

            if ($pass !== $passConfirm) {
                $error_registro = 'Las contraseñas no coinciden';
                require_once 'views/auth/login.php';
                return;
            }

            if ($modelo->existeEmail($email)) {
                $error_registro = 'Email ya registrado';
                require_once 'views/auth/login.php';
                return;
            }

            $nuevoId = $modelo->crear($usuario, $email, $pass);

            if ($nuevoId) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $nuevoId;
                $_SESSION['usuario_nombre'] = $usuario;
                header('Location: index.php');
                exit();
            } else {
                $error_registro = 'Error al registrar';
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
        session_destroy();
        header('Location: index.php?controller=auth&action=login');
        exit();
    }

    public function perfil() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        $usuarioModel = new Usuario();
        $recetaModel = new Receta();
        $usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);
        $misRecetas = $recetaModel->contarRecetasPorUsuario($_SESSION['usuario_id']);
        $misFavoritos = $recetaModel->contarFavoritos($_SESSION['usuario_id']);

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

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);

        if (!$usuario) {
            $_SESSION['mensaje_flash'] = 'No se pudo cargar el perfil';
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php');
            exit();
        }

        if ($nombre === '' || $email === '') {
            $_SESSION['mensaje_flash'] = 'Nombre y email son obligatorios';
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['mensaje_flash'] = 'Email no válido';
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        if ($usuarioModel->existeEmailExceptoId($email, $_SESSION['usuario_id'])) {
            $_SESSION['mensaje_flash'] = 'Ese email ya está en uso';
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        $cambiarPassword = ($passwordNueva !== '' || $passwordConfirmar !== '' || $passwordActual !== '');
        if ($cambiarPassword) {
            if ($passwordNueva === '' || $passwordConfirmar === '' || $passwordActual === '') {
                $_SESSION['mensaje_flash'] = 'Completa la contraseña actual y la nueva';
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=auth&action=perfil');
                exit();
            }

            if ($passwordNueva !== $passwordConfirmar) {
                $_SESSION['mensaje_flash'] = 'Las contraseñas nuevas no coinciden';
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=auth&action=perfil');
                exit();
            }

            if (!$usuario || !password_verify($passwordActual, $usuario['password'])) {
                $_SESSION['mensaje_flash'] = 'La contraseña actual no es correcta';
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=auth&action=perfil');
                exit();
            }
        }

        if (!$usuarioModel->actualizarPerfil($_SESSION['usuario_id'], $nombre, $email)) {
            $_SESSION['mensaje_flash'] = 'No se pudo actualizar el perfil';
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=auth&action=perfil');
            exit();
        }

        if ($passwordNueva !== '' || $passwordConfirmar !== '' || $passwordActual !== '') {
            if (!$usuarioModel->cambiarPassword($_SESSION['usuario_id'], $passwordNueva)) {
                $_SESSION['mensaje_flash'] = 'No se pudo cambiar la contraseña';
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=auth&action=perfil');
                exit();
            }
        }

        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['mensaje_flash'] = 'Perfil actualizado correctamente';
        $_SESSION['tipo_flash'] = 'success';
        header('Location: index.php?controller=auth&action=perfil');
        exit();
    }
}
