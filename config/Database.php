<?php
// Gestor de conexiones a la base de datos (patrón Singleton)
class Database {
    private static $instancia = null;

    public static function conectar() {
        if (self::$instancia === null) {
            $servidor = 'localhost';
            $usuario  = 'root';
            $password = '';
            $bd       = 'recetario';

            $conexion = new mysqli($servidor, $usuario, $password, $bd);

            if ($conexion->connect_error) {
                die('Error de conexión: ' . $conexion->connect_error);
            }
            $conexion->set_charset('utf8');
            self::$instancia = $conexion;
        }
        return self::$instancia;
    }

    // Evitar clonación e instanciación directa
    private function __construct() {}
    private function __clone() {}
}
