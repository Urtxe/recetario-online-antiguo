<?php
require_once 'config/Database.php';

class Usuario {
    private $db;

    public function __construct() {
        $this->db = Database::conectar();
    }

    // Autenticar usuario
    public function login($email, $password) {
        $sql = "SELECT id, nombre, email, password FROM usuarios WHERE email = ?";
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

    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT id, nombre, email, password FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function existeEmailExceptoId($email, $id) {
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function actualizarPerfil($id, $nombre, $email) {
        $sql = "UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $nombre, $email, $id);
        return $stmt->execute();
    }

    public function cambiarPassword($id, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $passwordHash, $id);
        return $stmt->execute();
    }

    public function contarUsuarios() {
        $res = $this->db->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc();
        return (int)$res['total'];
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
