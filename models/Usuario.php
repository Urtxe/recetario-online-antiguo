<?php
require_once 'config/Database.php';

class Usuario {
    private $db;

    public function __construct() {
        $this->db = Database::conectar();
    }

    // Autenticar usuario
    public function login($email, $password) {
        $sql = "SELECT id, nombre, password FROM usuarios WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($user = $resultado->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    // Crear nuevo usuario
    public function crear($nombre, $email, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sss", $nombre, $email, $passwordHash);
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    // Verificar si email existe
    public function existeEmail($email) {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    // Comprobar si es favorito
    public function esFavorito($usuario_id, $receta_id) {
        $sql = "SELECT * FROM favoritos WHERE usuario_id = ? AND receta_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $usuario_id, $receta_id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    // Alternar estado de favorito
    public function toggleFavorito($usuario_id, $receta_id) {
        if ($this->esFavorito($usuario_id, $receta_id)) {
            $sql = "DELETE FROM favoritos WHERE usuario_id = ? AND receta_id = ?";
        } else {
            $sql = "INSERT INTO favoritos (usuario_id, receta_id) VALUES (?, ?)";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $usuario_id, $receta_id);
        return $stmt->execute();
    }
}