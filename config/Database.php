<?php
// Gestor de conexiones a la base de datos (patrón Singleton)
class Database {
    private static $instancia = null;

    public static function conectar() {
        if (self::$instancia === null) {
            $servidor = getenv('DB_HOST') ?: 'localhost';
            $usuario  = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') ?: '';
            $bd       = getenv('DB_NAME') ?: 'recetario';

            $conexion = new mysqli($servidor, $usuario, $password, $bd);

            if ($conexion->connect_error) {
                throw new RuntimeException('Error de conexión: ' . $conexion->connect_error);
            }
            $conexion->set_charset('utf8mb4');
            self::$instancia = $conexion;
        }
        return self::$instancia;
    }

    // Evitar clonación e instanciación directa
    private function __construct() {}
    private function __clone() {}
}
