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

function rate_limit_storage_path($key) {
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'recetario_login_' . sha1($key) . '.json';
}

function rate_limit_check($key, $maxAttempts = 5, $windowSeconds = 900) {
    $path = rate_limit_storage_path($key);

    if (!file_exists($path)) {
        return [true, 0, 0];
    }

    $raw = file_get_contents($path);
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return [true, 0, 0];
    }

    $attempts = array_values(array_filter($data['attempts'] ?? [], function ($timestamp) use ($windowSeconds) {
        return is_int($timestamp) && $timestamp >= (time() - $windowSeconds);
    }));

    if (count($attempts) >= $maxAttempts) {
        $retryAfter = $windowSeconds - (time() - min($attempts));
        return [false, count($attempts), max(0, $retryAfter)];
    }

    file_put_contents($path, json_encode(['attempts' => $attempts]), LOCK_EX);
    return [true, count($attempts), 0];
}

function rate_limit_register_failure($key, $windowSeconds = 900) {
    $path = rate_limit_storage_path($key);
    $data = ['attempts' => []];

    if (file_exists($path)) {
        $raw = file_get_contents($path);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    $data['attempts'] = array_values(array_filter($data['attempts'] ?? [], function ($timestamp) use ($windowSeconds) {
        return is_int($timestamp) && $timestamp >= (time() - $windowSeconds);
    }));
    $data['attempts'][] = time();

    file_put_contents($path, json_encode($data), LOCK_EX);
}

function rate_limit_clear($key) {
    $path = rate_limit_storage_path($key);
    if (file_exists($path)) {
        @unlink($path);
    }
}
