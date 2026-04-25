<?php

abstract class BaseController {
    protected function requireLogin() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }
    }

    protected function requirePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método no permitido');
        }
    }

    protected function redirect($url) {
        header('Location: ' . $url);
        exit();
    }

    protected function jsonResponse(array $data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }

    protected function requireAdminUser($user) {
        if (!$user || !is_admin_user($user)) {
            http_response_code(403);
            exit('Acceso denegado');
        }
    }
}
