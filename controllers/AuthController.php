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
                header('Location: index.php');
                exit();
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
        session_destroy();
        header('Location: index.php?controller=auth&action=login');
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
}
