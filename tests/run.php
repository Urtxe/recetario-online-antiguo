<?php

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/RecipeService.php';

date_default_timezone_set('UTC');

class FakeResult {
    private $rows;
    private $index = 0;

    public function __construct($rows) {
        $this->rows = array_values($rows);
    }

    public function fetch_assoc() {
        if ($this->index >= count($this->rows)) {
            return null;
        }

        return $this->rows[$this->index++];
    }

    public function num_rows() {
        return count($this->rows);
    }
}

class FakeUsuarioRepo {
    public $users;
    public $favorites = [];

    public function __construct() {
        $this->users = [
            1 => ['id' => 1, 'nombre' => 'Admin', 'email' => 'admin@example.com', 'password' => password_hash('secret', PASSWORD_DEFAULT), 'rol' => 'admin', 'two_factor_enabled' => 0, 'two_factor_secret' => null],
            2 => ['id' => 2, 'nombre' => 'Mikel', 'email' => 'mikel@example.com', 'password' => password_hash('secret', PASSWORD_DEFAULT), 'rol' => 'usuario', 'two_factor_enabled' => 0, 'two_factor_secret' => null],
            3 => ['id' => 3, 'nombre' => 'Admin 2FA', 'email' => 'admin-2fa@example.com', 'password' => password_hash('secret', PASSWORD_DEFAULT), 'rol' => 'admin', 'two_factor_enabled' => 1, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP'],
        ];
    }

    public function login($email, $password) {
        foreach ($this->users as $user) {
            if ($user['email'] === $email && password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public function existeEmail($email) {
        foreach ($this->users as $user) {
            if ($user['email'] === $email) {
                return true;
            }
        }
        return false;
    }

    public function crear($nombre, $email, $password) {
        $id = count($this->users) + 1;
        $this->users[$id] = [
            'id' => $id,
            'nombre' => $nombre,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
        return $id;
    }

    public function obtenerPorId($id) {
        return $this->users[$id] ?? null;
    }

    public function obtenerPorIdConSeguridad($id) {
        return $this->obtenerPorId($id);
    }

    public function obtenerPorEmail($email) {
        foreach ($this->users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }
        return null;
    }

    public function existeEmailExceptoId($email, $id) {
        foreach ($this->users as $user) {
            if ($user['email'] === $email && $user['id'] != $id) {
                return true;
            }
        }
        return false;
    }

    public function actualizarPerfil($id, $nombre, $email) {
        if (!isset($this->users[$id])) {
            return false;
        }
        $this->users[$id]['nombre'] = $nombre;
        $this->users[$id]['email'] = $email;
        return true;
    }

    public function cambiarPassword($id, $password) {
        if (!isset($this->users[$id])) {
            return false;
        }
        $this->users[$id]['password'] = password_hash($password, PASSWORD_DEFAULT);
        return true;
    }

    public function toggleFavorito($usuario_id, $receta_id) {
        $key = $usuario_id . ':' . $receta_id;
        if (isset($this->favorites[$key])) {
            unset($this->favorites[$key]);
        } else {
            $this->favorites[$key] = true;
        }
        return true;
    }

    public function esFavorito($usuario_id, $receta_id) {
        return isset($this->favorites[$usuario_id . ':' . $receta_id]);
    }

    public function contarUsuarios() {
        return count($this->users);
    }
}

class FakeRecetaRepo {
    public $recipes;

    public function __construct() {
        $this->recipes = [
            10 => [
                'id' => 10,
                'titulo' => 'Tortilla',
                'duracion' => 20,
                'dificultad' => 'facil',
                'origen' => 'española',
                'tipo' => 'principal',
                'preparacion' => 'Batir y cuajar',
                'imagen_url' => 'tortilla.jpg',
                'usuario_id' => 2,
                'nombre_autor' => 'Mikel'
            ]
        ];
    }

    public function iniciarTransaccion() {}
    public function confirmarTransaccion() {}
    public function revertirTransaccion() {}

    public function listarConFiltros($filtros, $limit, $offset) {
        return new FakeResult(array_values($this->recipes));
    }

    public function contarTotal($filtros) {
        return count($this->recipes);
    }

    public function obtenerIdsFavoritos($usuario_id) {
        return [10];
    }

    public function obtenerPorId($id) {
        return $this->recipes[$id] ?? null;
    }

    public function obtenerIngredientes($id) {
        return new FakeResult([
            ['nombre' => 'Huevo', 'cantidad' => '2'],
            ['nombre' => 'Patata', 'cantidad' => '3']
        ]);
    }

    public function crear($datos, $usuario_id) {
        $this->recipes[11] = [
            'id' => 11,
            'titulo' => $datos['titulo'],
            'duracion' => $datos['duracion'],
            'dificultad' => $datos['dificultad'],
            'origen' => $datos['origen'],
            'tipo' => $datos['tipo'],
            'preparacion' => $datos['preparacion'],
            'imagen_url' => $datos['imagen'],
            'usuario_id' => $usuario_id,
            'nombre_autor' => 'Mikel'
        ];
        return 11;
    }

    public function guardarIngredientes($receta_id, $nombres, $cantidades) {
        return true;
    }

    public function actualizar($id, $datos) {
        if (!isset($this->recipes[$id])) {
            return false;
        }
        $this->recipes[$id]['titulo'] = $datos['titulo'];
        $this->recipes[$id]['preparacion'] = $datos['preparacion'];
        $this->recipes[$id]['imagen_url'] = $datos['imagen'];
        return true;
    }

    public function borrarIngredientes($receta_id) {
        return true;
    }

    public function borrar($id) {
        unset($this->recipes[$id]);
        return true;
    }

    public function obtenerFavoritos($usuario_id, $limit = 9, $offset = 0) {
        return new FakeResult(array_values($this->recipes));
    }

    public function contarFavoritos($usuario_id) {
        return 1;
    }

    public function obtenerTodasParaModeracion($limit, $offset) {
        return new FakeResult(array_values($this->recipes));
    }

    public function contarTodasParaModeracion() {
        return count($this->recipes);
    }

    public function contarRecetasPorUsuario($usuario_id) {
        return count($this->recipes);
    }
}

function assert_true($condition, $message) {
    if (!$condition) {
        throw new Exception($message);
    }
}

function assert_equals($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new Exception($message . ' | expected: ' . var_export($expected, true) . ' got: ' . var_export($actual, true));
    }
}

$tests = [];

$tests['login'] = function () {
    $users = new FakeUsuarioRepo();
    $recipes = new FakeRecetaRepo();
    $service = new AuthService($users, $recipes);
    $keyEmail = 'test-' . uniqid() . '@example.com';
    rate_limit_clear('login|' . strtolower($keyEmail) . '|127.0.0.1');

    $ok = $service->login('admin@example.com', 'secret', '127.0.0.1');
    assert_true($ok['success'], 'Login debería funcionar');
    assert_equals('admin@example.com', $ok['user']['email'], 'El email debe volver en login');

    for ($i = 0; $i < 5; $i++) {
        $service->login($keyEmail, 'bad', '127.0.0.1');
    }
    $blocked = $service->login($keyEmail, 'bad', '127.0.0.1');
    assert_true(!$blocked['success'], 'El rate limit debe bloquear');
    assert_true(str_contains($blocked['error'], 'Demasiados intentos'), 'El mensaje debe indicar bloqueo');
};

$tests['two_factor'] = function () {
    $previousIp = $_SERVER['REMOTE_ADDR'] ?? null;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    try {
        $users = new FakeUsuarioRepo();
        $recipes = new FakeRecetaRepo();
        $service = new AuthService($users, $recipes);
        $user = $users->obtenerPorEmail('admin-2fa@example.com');
        $code = totp_code($user['two_factor_secret']);

        rate_limit_clear('2fa|admin-2fa@example.com|127.0.0.1');
        $login = $service->login('admin-2fa@example.com', 'secret', '127.0.0.1');
        assert_true(!empty($login['requires_2fa']), 'El admin con 2FA debe pedir código');

        $valid = $service->verifyAdminTwoFactor($user, $code);
        assert_true($valid['success'], 'El código 2FA válido debe funcionar');

        rate_limit_clear('2fa|admin-2fa@example.com|127.0.0.1');
        for ($i = 0; $i < 8; $i++) {
            $service->verifyAdminTwoFactor($user, '000000');
        }
        $blocked = $service->verifyAdminTwoFactor($user, '000000');

        assert_true(!$blocked['success'], 'El 2FA debe bloquear tras varios fallos');
        assert_true(str_contains($blocked['error'], 'Demasiados intentos 2FA'), 'El mensaje debe indicar bloqueo 2FA');
    } finally {
        if ($previousIp === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $previousIp;
        }
    }
};

$tests['profile'] = function () {
    $users = new FakeUsuarioRepo();
    $recipes = new FakeRecetaRepo();
    $service = new AuthService($users, $recipes);

    $result = $service->updateProfile(2, 'Mikel Urtxegi', 'mikel+new@example.com', 'secret', 'nueva123', 'nueva123');
    assert_true($result['success'], 'El perfil debería actualizarse');
    assert_equals('Mikel Urtxegi', $result['usuario']['nombre'], 'El nombre debe cambiar');
    assert_equals('mikel+new@example.com', $result['usuario']['email'], 'El email debe cambiar');

    $duplicateEmail = $service->updateProfile(2, 'Mikel Urtxegi', 'admin@example.com');
    assert_true(!$duplicateEmail['success'], 'No se debe permitir un email duplicado');
};

$tests['favorites'] = function () {
    $users = new FakeUsuarioRepo();
    $recipes = new FakeRecetaRepo();
    $service = new RecipeService($recipes, $users);

    $toggle1 = $service->toggleFavorite(2, 10);
    assert_true($toggle1['success'], 'Debe poder alternar favorito');
    assert_true($toggle1['favorito'], 'La primera alternancia debería marcarlo como favorito');

    $toggle2 = $service->toggleFavorite(2, 10);
    assert_true(!$toggle2['favorito'], 'La segunda alternancia debería quitarlo de favorito');
};

$tests['api'] = function () {
    $users = new FakeUsuarioRepo();
    $recipes = new FakeRecetaRepo();
    $users->toggleFavorito(2, 10);
    $service = new RecipeService($recipes, $users);

    $list = $service->listData(['buscar' => ''], 1, 12, 2);
    assert_true(isset($list['recetas']), 'La lista debe traer recetas');
    assert_true(isset($list['favoritos_ids'][10]), 'Debe marcar favoritos');

    $detail = $service->detailData(10, 2);
    assert_true($detail['success'], 'El detalle debe existir');
    assert_equals(2, count($detail['ingredientes']), 'Debe devolver ingredientes');
    assert_true($detail['es_favorito'], 'Debe marcar favorito en detalle');
};

$tests['admin_helper'] = function () {
    $previous = getenv('ADMIN_EMAIL');
    putenv('ADMIN_EMAIL=admin@example.com');
    assert_true(is_admin_user(['email' => 'admin@example.com']), 'La detección de admin debe funcionar');
    assert_true(!is_admin_user(['email' => 'user@example.com']), 'Un usuario normal no debe ser admin');
    assert_true(is_admin_user(['usuario_rol' => 'admin', 'email' => 'user@example.com']), 'El rol admin debe mandar sobre el email');
    putenv('ADMIN_EMAIL=' . ($previous !== false ? $previous : ''));
};

$passed = 0;
$failed = 0;
$failures = [];

foreach ($tests as $name => $test) {
    try {
        $test();
        $passed++;
        echo "[PASS] {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        $failures[] = "[FAIL] {$name}: " . $e->getMessage();
        echo "[FAIL] {$name}: " . $e->getMessage() . "\n";
    }
}

echo "\nPassed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed > 0) {
    exit(1);
}

exit(0);
