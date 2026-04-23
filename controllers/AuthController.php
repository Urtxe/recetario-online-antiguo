<?php
require_once 'models/Usuario.php';

class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            $usuarioModel = new Usuario();
            $user = $usuarioModel->login($email, $password);
            
            if ($user) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre'];
                header('Location: index.php');
                exit();
            } else {
                $error_login = 'Usuario o contraseña incorrectos';
            }
        }
        require_once 'views/auth/login.php';
    }

    public function registro() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        session_destroy();
        header('Location: index.php?controller=auth&action=login');
        exit();
    }
}