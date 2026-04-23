<?php

function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_meta_tag() {
    return '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token($token) {
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['_csrf_token'])) {
        return false;
    }

    return is_string($token) && hash_equals($_SESSION['_csrf_token'], $token);
}

function require_csrf_token($token) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método no permitido');
    }

    if (!verify_csrf_token($token)) {
        http_response_code(419);
        exit('Token CSRF inválido');
    }
}
