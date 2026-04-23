<?php
// Enrutador Principal (Front Controller)
require_once 'config/helpers.php';
configure_session_security();
session_start();
send_security_headers();

$rutas_permitidas = [
    'auth' => ['login', 'registro', 'logout', 'perfil', 'actualizarPerfil', 'verificar2fa', 'activar2fa', 'desactivar2fa', 'solicitarRecuperacion', 'resetearPassword'],
    'api' => ['recetas', 'receta'],
    'admin' => ['dashboard', 'borrarReceta'],
    'receta' => ['index', 'ver', 'crear', 'guardar', 'editar', 'actualizar', 'borrar', 'misFavoritos', 'favorito']
];

// Determinar controlador y acción
if (!isset($_SESSION['usuario_id'])) {
    // Usuario no logueado: solo permitir 'auth'
    if (isset($_GET['controller']) && $_GET['controller'] === 'auth') {
        $controller = $_GET['controller'];
        $action = $_GET['action'] ?? 'login';
    } else {
        $controller = 'auth';
        $action = 'login';
    }
} else {
    // Usuario logueado: ir a 'receta' por defecto
    $controller = $_GET['controller'] ?? 'receta';
    $action = $_GET['action'] ?? 'index';
}

if (!isset($rutas_permitidas[$controller]) || !in_array($action, $rutas_permitidas[$controller], true)) {
    http_response_code(404);
    echo 'Error: ruta no permitida';
    exit();
}

// Cargar controlador dinámicamente
$controller_name = ucfirst($controller) . 'Controller';
$controller_file = 'controllers/' . $controller_name . '.php';

if (file_exists($controller_file)) {
    require_once $controller_file;
    $controlador = new $controller_name();
    
    if (method_exists($controlador, $action)) {
        // Pasar ID si existe en URL
        isset($_GET['id']) ? $controlador->$action($_GET['id']) : $controlador->$action();
    } else {
        echo 'Error: método no existe';
    }
} else {
    echo 'Error: controlador no encontrado';
}
