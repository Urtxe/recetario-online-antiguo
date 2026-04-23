<?php

require_once 'models/Usuario.php';
require_once 'models/Receta.php';
require_once 'config/helpers.php';

class AuthService {
    private $usuarioModel;
    private $recetaModel;

    public function __construct($usuarioModel = null, $recetaModel = null) {
        $this->usuarioModel = $usuarioModel ?: new Usuario();
        $this->recetaModel = $recetaModel ?: new Receta();
    }

    public function login($email, $password, $ip = null) {
        $limiterKey = 'login|' . strtolower($email) . '|' . ($ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        list($puedeIntentar, $intentos, $retryAfter) = rate_limit_check($limiterKey);

        if (!$puedeIntentar) {
            return [
                'success' => false,
                'error' => 'Demasiados intentos. Prueba de nuevo en ' . max(1, (int)ceil($retryAfter / 60)) . ' minuto(s).'
            ];
        }

        $user = $this->usuarioModel->login($email, $password);
        if ($user) {
            rate_limit_clear($limiterKey);
            return [
                'success' => true,
                'user' => $user
            ];
        }

        rate_limit_register_failure($limiterKey);
        return [
            'success' => false,
            'error' => 'Usuario o contraseña incorrectos'
        ];
    }

    public function register($nombre, $email, $password, $passwordConfirm) {
        if ($password !== $passwordConfirm) {
            return ['success' => false, 'error' => 'Las contraseñas no coinciden'];
        }

        if ($this->usuarioModel->existeEmail($email)) {
            return ['success' => false, 'error' => 'Email ya registrado'];
        }

        $nuevoId = $this->usuarioModel->crear($nombre, $email, $password);
        if (!$nuevoId) {
            return ['success' => false, 'error' => 'Error al registrar'];
        }

        return [
            'success' => true,
            'user' => [
                'id' => $nuevoId,
                'nombre' => $nombre,
                'email' => $email
            ]
        ];
    }

    public function getProfileSummary($userId) {
        $usuario = $this->usuarioModel->obtenerPorId($userId);
        if (!$usuario) {
            return ['success' => false, 'error' => 'No se pudo cargar el perfil'];
        }

        return [
            'success' => true,
            'usuario' => $usuario,
            'misRecetas' => $this->recetaModel->contarRecetasPorUsuario($userId),
            'misFavoritos' => $this->recetaModel->contarFavoritos($userId)
        ];
    }

    public function updateProfile($userId, $nombre, $email, $passwordActual = '', $passwordNueva = '', $passwordConfirmar = '') {
        $usuario = $this->usuarioModel->obtenerPorId($userId);
        if (!$usuario) {
            return ['success' => false, 'error' => 'No se pudo cargar el perfil'];
        }

        if ($nombre === '' || $email === '') {
            return ['success' => false, 'error' => 'Nombre y email son obligatorios'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email no válido'];
        }

        if ($this->usuarioModel->existeEmailExceptoId($email, $userId)) {
            return ['success' => false, 'error' => 'Ese email ya está en uso'];
        }

        $cambiarPassword = ($passwordNueva !== '' || $passwordConfirmar !== '' || $passwordActual !== '');
        if ($cambiarPassword) {
            if ($passwordNueva === '' || $passwordConfirmar === '' || $passwordActual === '') {
                return ['success' => false, 'error' => 'Completa la contraseña actual y la nueva'];
            }

            if ($passwordNueva !== $passwordConfirmar) {
                return ['success' => false, 'error' => 'Las contraseñas nuevas no coinciden'];
            }

            if (!password_verify($passwordActual, $usuario['password'])) {
                return ['success' => false, 'error' => 'La contraseña actual no es correcta'];
            }
        }

        if (!$this->usuarioModel->actualizarPerfil($userId, $nombre, $email)) {
            return ['success' => false, 'error' => 'No se pudo actualizar el perfil'];
        }

        if ($cambiarPassword && !$this->usuarioModel->cambiarPassword($userId, $passwordNueva)) {
            return ['success' => false, 'error' => 'No se pudo cambiar la contraseña'];
        }

        return ['success' => true, 'usuario' => $this->usuarioModel->obtenerPorId($userId)];
    }
}
