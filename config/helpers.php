<?php
require_once 'config/Database.php';

function app_env($key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function app_bool_env($key, $default = false) {
    $value = strtolower((string)app_env($key, $default ? '1' : '0'));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function app_is_https() {
    if (app_bool_env('APP_FORCE_HTTPS', false)) {
        return true;
    }

    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    return false;
}

function configure_session_security() {
    if (PHP_SAPI === 'cli') {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', app_is_https() ? '1' : '0');

    $params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $params['lifetime'] ?? 0,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => app_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function send_security_headers() {
    if (headers_sent()) {
        return;
    }

    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: same-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'");

    if (app_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function storage_path($relative) {
    $base = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
    return $base . ltrim($relative, DIRECTORY_SEPARATOR);
}

function upload_storage_path($relative = '') {
    $baseDir = app_env('UPLOAD_DIR', 'storage/uploads');
    $base = storage_path($baseDir);
    if ($relative === '') {
        return $base;
    }

    return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
}

function upload_legacy_public_path($relative = '') {
    $base = storage_path('public/uploads');
    if ($relative === '') {
        return $base;
    }

    return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
}

function upload_delivery_mode() {
    return strtolower(trim(app_env('UPLOAD_DELIVERY_MODE', 'controller')));
}

function recipe_image_url($filename) {
    if (empty($filename)) {
        return '';
    }

    $safeName = rawurlencode(basename((string)$filename));
    if (upload_delivery_mode() === 'static') {
        $prefix = trim(app_env('UPLOAD_URL_PREFIX', 'public/uploads'), '/');
        return $prefix . '/' . $safeName;
    }

    return 'index.php?controller=file&action=imagen&name=' . $safeName;
}

function ensure_directory($path) {
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

function rotate_security_log($path, $maxBytes = 5242880, $keep = 3) {
    if (!file_exists($path) || filesize($path) < $maxBytes) {
        return;
    }

    for ($i = $keep - 1; $i >= 1; $i--) {
        $source = $path . '.' . $i;
        $target = $path . '.' . ($i + 1);
        if (file_exists($source)) {
            @rename($source, $target);
        }
    }

    @rename($path, $path . '.1');
}

function security_log($event, array $context = [], $level = 'info') {
    $dir = storage_path('storage/logs');
    ensure_directory($dir);
    $path = $dir . DIRECTORY_SEPARATOR . 'security.log';
    rotate_security_log($path);

    $line = json_encode([
        'ts' => date('c'),
        'level' => $level,
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'context' => $context
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function rate_limit_storage_path($key) {
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'recetario_login_' . sha1($key) . '.json';
}

function rate_limit_db_table_exists() {
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    try {
        $db = Database::conectar();
        $table = 'security_rate_limits';
        $res = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
        $exists = $res && $res->num_rows > 0;
    } catch (Throwable $e) {
        $exists = false;
    }

    return $exists;
}

function rate_limit_check_db($key, $maxAttempts, $windowSeconds) {
    $db = Database::conectar();
    $keyHash = hash('sha256', $key);
    $now = time();
    $windowStart = $now - $windowSeconds;
    static $pruned = false;

    if (!$pruned) {
        rate_limit_prune_db();
        $pruned = true;
    }

    $stmt = $db->prepare("SELECT attempts, window_started_at FROM security_rate_limits WHERE rate_key = ?");
    $stmt->bind_param("s", $keyHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        $stmt = $db->prepare("INSERT INTO security_rate_limits (rate_key, attempts, window_started_at, updated_at) VALUES (?, 0, ?, NOW())");
        $stmt->bind_param("si", $keyHash, $now);
        $stmt->execute();
        return [true, 0, 0];
    }

    $attempts = (int)$row['attempts'];
    $startedAt = (int)$row['window_started_at'];

    if ($startedAt < $windowStart) {
        $stmt = $db->prepare("UPDATE security_rate_limits SET attempts = 0, window_started_at = ?, updated_at = NOW() WHERE rate_key = ?");
        $stmt->bind_param("is", $now, $keyHash);
        $stmt->execute();
        return [true, 0, 0];
    }

    if ($attempts >= $maxAttempts) {
        return [false, $attempts, max(0, $windowSeconds - ($now - $startedAt))];
    }

    return [true, $attempts, 0];
}

function rate_limit_register_failure_db($key, $windowSeconds) {
    $db = Database::conectar();
    $keyHash = hash('sha256', $key);
    $now = time();
    $windowStart = $now - $windowSeconds;

    $stmt = $db->prepare("SELECT attempts, window_started_at FROM security_rate_limits WHERE rate_key = ?");
    $stmt->bind_param("s", $keyHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        $stmt = $db->prepare("INSERT INTO security_rate_limits (rate_key, attempts, window_started_at, updated_at) VALUES (?, 1, ?, NOW())");
        $stmt->bind_param("si", $keyHash, $now);
        return $stmt->execute();
    }

    $attempts = (int)$row['attempts'];
    $startedAt = (int)$row['window_started_at'];
    if ($startedAt < $windowStart) {
        $attempts = 0;
        $startedAt = $now;
    }

    $attempts++;
    $stmt = $db->prepare("UPDATE security_rate_limits SET attempts = ?, window_started_at = ?, updated_at = NOW() WHERE rate_key = ?");
    $stmt->bind_param("iis", $attempts, $startedAt, $keyHash);
    return $stmt->execute();
}

function rate_limit_clear_db($key) {
    $db = Database::conectar();
    $keyHash = hash('sha256', $key);
    $stmt = $db->prepare("DELETE FROM security_rate_limits WHERE rate_key = ?");
    $stmt->bind_param("s", $keyHash);
    return $stmt->execute();
}

function rate_limit_prune_db($days = 30) {
    $db = Database::conectar();
    $days = max(1, (int)$days);
    return $db->query("DELETE FROM security_rate_limits WHERE updated_at < (NOW() - INTERVAL {$days} DAY)");
}

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

function rate_limit_check($key, $maxAttempts = 5, $windowSeconds = 900) {
    if (rate_limit_db_table_exists()) {
        return rate_limit_check_db($key, $maxAttempts, $windowSeconds);
    }

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
    if (rate_limit_db_table_exists()) {
        return rate_limit_register_failure_db($key, $windowSeconds);
    }

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
    if (rate_limit_db_table_exists()) {
        rate_limit_clear_db($key);
        return;
    }

    $path = rate_limit_storage_path($key);
    if (file_exists($path)) {
        @unlink($path);
    }
}

function base32_encode_string($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    $bytes = unpack('C*', $data);
    foreach ($bytes as $byte) {
        $binary .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
    }

    $chunks = str_split($binary, 5);
    $output = '';
    foreach ($chunks as $chunk) {
        $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $output .= $alphabet[bindec($chunk)];
    }

    return $output;
}

function base32_decode_string($input) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $input = strtoupper(preg_replace('/[^A-Z2-7]/', '', $input));
    $binary = '';

    foreach (str_split($input) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) {
            continue;
        }
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }

    $bytes = str_split($binary, 8);
    $output = '';
    foreach ($bytes as $byte) {
        if (strlen($byte) === 8) {
            $output .= chr(bindec($byte));
        }
    }

    return $output;
}

function generate_totp_secret($bytes = 20) {
    return base32_encode_string(random_bytes($bytes));
}

function totp_code($secret, $timeSlice = null, $digits = 6, $period = 30) {
    $timeSlice = $timeSlice ?? floor(time() / $period);
    $secretKey = base32_decode_string($secret);
    $time = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('sha1', $time, $secretKey, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $binary = (
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF)
    );
    $otp = $binary % (10 ** $digits);
    return str_pad((string)$otp, $digits, '0', STR_PAD_LEFT);
}

function verify_totp_code($secret, $code, $window = 1, $digits = 6, $period = 30) {
    $code = preg_replace('/\s+/', '', (string)$code);
    $slice = floor(time() / $period);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_code($secret, $slice + $i, $digits, $period), $code)) {
            return true;
        }
    }
    return false;
}

function admin_email() {
    return strtolower(trim(getenv('ADMIN_EMAIL') ?: ''));
}

function is_admin_user($user = null) {
    if (!$user) {
        return false;
    }

    $role = strtolower((string)($user['rol'] ?? $user['role'] ?? $user['usuario_rol'] ?? ''));
    if ($role === 'admin') {
        return true;
    }

    $adminEmail = admin_email();
    return $adminEmail !== '' && strtolower($user['email'] ?? '') === $adminEmail;
}

function password_reset_storage_path() {
    return storage_path('storage/password_resets.json');
}

function store_password_reset_token($email, $tokenHash, $expiresAt) {
    $path = password_reset_storage_path();
    ensure_directory(dirname($path));
    prune_password_reset_storage($path);

    $data = [];
    if (file_exists($path)) {
        $decoded = json_decode(file_get_contents($path), true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    $data[] = [
        'email' => strtolower($email),
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
        'used_at' => null
    ];

    file_put_contents($path, json_encode($data), LOCK_EX);
    return true;
}

function find_password_reset_entry($token) {
    $path = password_reset_storage_path();
    if (!file_exists($path)) {
        return null;
    }

    prune_password_reset_storage($path);
    $decoded = json_decode(file_get_contents($path), true);
    if (!is_array($decoded)) {
        return null;
    }

    $tokenHash = hash('sha256', $token);
    foreach ($decoded as $entry) {
        if (($entry['token_hash'] ?? '') === $tokenHash && empty($entry['used_at']) && (int)($entry['expires_at'] ?? 0) >= time()) {
            return $entry;
        }
    }

    return null;
}

function consume_password_reset_token($token) {
    $path = password_reset_storage_path();
    if (!file_exists($path)) {
        return false;
    }

    prune_password_reset_storage($path);
    $decoded = json_decode(file_get_contents($path), true);
    if (!is_array($decoded)) {
        return false;
    }

    $tokenHash = hash('sha256', $token);
    $changed = false;
    foreach ($decoded as &$entry) {
        if (($entry['token_hash'] ?? '') === $tokenHash && empty($entry['used_at']) && (int)($entry['expires_at'] ?? 0) >= time()) {
            $entry['used_at'] = time();
            $changed = true;
            break;
        }
    }
    unset($entry);

    if ($changed) {
        file_put_contents($path, json_encode($decoded), LOCK_EX);
    }

    return $changed;
}

function prune_password_reset_storage($path = null) {
    $path = $path ?: password_reset_storage_path();
    if (!file_exists($path)) {
        return false;
    }

    $decoded = json_decode(file_get_contents($path), true);
    if (!is_array($decoded)) {
        return false;
    }

    $now = time();
    $filtered = array_values(array_filter($decoded, function ($entry) use ($now) {
        return is_array($entry)
            && !empty($entry['email'])
            && !empty($entry['token_hash'])
            && empty($entry['used_at'])
            && (int)($entry['expires_at'] ?? 0) >= $now;
    }));

    if ($filtered !== $decoded) {
        file_put_contents($path, json_encode($filtered), LOCK_EX);
    }

    return true;
}
