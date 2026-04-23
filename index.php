<?php
// Enrutador Principal (Front Controller)
session_start();

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