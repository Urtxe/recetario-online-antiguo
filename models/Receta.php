<?php
require_once 'config/Database.php';

class Receta {
    private $db;
    private $cacheOrigenes = null;
    private $cacheTipos = null;
    private $cacheUsuarios = null;
    private $cacheFullTextRecetas = null;

    public function __construct() {
        $this->db = Database::conectar();
    }

    public function iniciarTransaccion() {
        $this->db->begin_transaction();
    }

    public function confirmarTransaccion() {
        $this->db->commit();
    }

    public function revertirTransaccion() {
        $this->db->rollback();
    }

    // Listar con filtros y paginación
    public function listarConFiltros($filtros, $limit, $offset) {
        $sql = "SELECT recetas.*, usuarios.nombre AS nombre_autor 
                FROM recetas 
                JOIN usuarios ON recetas.usuario_id = usuarios.id";
        
        list($where, $params, $types) = $this->construirWhere($filtros);

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY recetas.id DESC LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    // Contar total de registros
    public function contarTotal($filtros) {
        $sql = "SELECT COUNT(*) as total FROM recetas";
        list($where, $params, $types) = $this->construirWhere($filtros);

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res['total'];
    }

    // Generar condiciones donde dinámicamente
    private function construirWhere($filtros) {
        $where = [];
        $params = [];
        $types = "";

        if (!empty($filtros['buscar'])) {
            $where[] = $this->condicionBusquedaTextoCompleto($filtros['buscar']);
            $busquedaParams = $this->parametrosBusquedaTextoCompleto($filtros['buscar']);
            foreach ($busquedaParams as $param) {
                $params[] = $param;
                $types .= "s";
            }
        }

        if (!empty($filtros['ingredientes'])) {
            $where[] = $this->condicionBusquedaIngredientes($filtros['ingredientes']);
            $types .= "s";
            $params[] = "%" . $filtros['ingredientes'] . "%";
        }

        if (!empty($filtros['origen'])) {
            $where[] = "recetas.origen = ?";
            $types .= "s";
            $params[] = $filtros['origen'];
        }

        if (!empty($filtros['dificultad'])) {
            $where[] = "recetas.dificultad = ?";
            $types .= "s";
            $params[] = $filtros['dificultad'];
        }

        if (!empty($filtros['tipo'])) {
            $where[] = "recetas.tipo = ?";
            $types .= "s";
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['duracion'])) {
            switch ($filtros['duracion']) {
                case '0-30': $where[] = "recetas.duracion <= 30"; break;
                case '30-60': $where[] = "recetas.duracion > 30 AND recetas.duracion <= 60"; break;
                case '60-120': $where[] = "recetas.duracion > 60 AND recetas.duracion <= 120"; break;
                case '120+': $where[] = "recetas.duracion > 120"; break;
            }
        }

        if (!empty($filtros['usuario'])) {
            $where[] = "recetas.usuario_id = ?";
            $types .= "i";
            $params[] = $filtros['usuario'];
        }

        return [$where, $params, $types];
    }

    private function puedeUsarBusquedaTextoCompleto($texto) {
        $texto = trim((string)$texto);
        if ($texto === '') {
            return false;
        }

        return $this->tieneIndiceTextoCompleto('recetas', 'idx_recetas_busqueda')
            && (strlen($texto) >= 3);
    }

    private function condicionBusquedaTextoCompleto($texto) {
        $texto = trim((string)$texto);
        if ($texto === '') {
            return '1=1';
        }

        if ($this->puedeUsarBusquedaTextoCompleto($texto)) {
            return "("
                . "MATCH(recetas.titulo, recetas.preparacion) AGAINST (? IN BOOLEAN MODE)"
                . " OR EXISTS (SELECT 1 FROM ingredientes WHERE ingredientes.receta_id = recetas.id AND ingredientes.nombre LIKE ?)"
                . " OR recetas.titulo LIKE ?"
                . " OR recetas.preparacion LIKE ?"
                . " OR usuarios.nombre LIKE ?"
                . ")";
        }

        return "("
            . "recetas.titulo LIKE ?"
            . " OR recetas.preparacion LIKE ?"
            . " OR EXISTS (SELECT 1 FROM ingredientes WHERE ingredientes.receta_id = recetas.id AND ingredientes.nombre LIKE ?)"
            . " OR usuarios.nombre LIKE ?"
            . ")";
    }

    private function parametrosBusquedaTextoCompleto($texto) {
        $texto = trim((string)$texto);
        if ($texto === '') {
            return [];
        }

        $like = '%' . $texto . '%';
        if ($this->puedeUsarBusquedaTextoCompleto($texto)) {
            $boolean = $this->textoABooleanoFulltext($texto);
            return [$boolean, $like, $like, $like, $like];
        }

        return [$like, $like, $like, $like];
    }

    private function textoABooleanoFulltext($texto) {
        $palabras = preg_split('/\s+/', trim((string)$texto));
        $terminos = [];
        foreach ($palabras as $palabra) {
            $palabra = preg_replace('/[^\pL\pN]+/u', '', $palabra);
            if (mb_strlen($palabra) >= 2) {
                $terminos[] = '+' . $palabra . '*';
            }
        }

        return implode(' ', $terminos);
    }

    private function condicionBusquedaIngredientes($texto) {
        return "EXISTS (SELECT 1 FROM ingredientes WHERE ingredientes.receta_id = recetas.id AND ingredientes.nombre LIKE ?)";
    }

    private function tieneIndiceTextoCompleto($tabla, $indexName) {
        if ($tabla === 'recetas' && $this->cacheFullTextRecetas !== null) {
            return $this->cacheFullTextRecetas;
        }

        $sql = "SELECT 1 FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = ?
                  AND index_name = ?
                  AND index_type = 'FULLTEXT'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $tabla, $indexName);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;

        if ($tabla === 'recetas') {
            $this->cacheFullTextRecetas = $exists;
        }

        return $exists;
    }

    // Obtener por ID
    public function obtenerPorId($id) {
        $sql = "SELECT r.*, u.nombre AS nombre_autor FROM recetas r 
                JOIN usuarios u ON r.usuario_id = u.id WHERE r.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Obtener ingredientes
    public function obtenerIngredientes($id_receta) {
        $sql = "SELECT * FROM ingredientes WHERE receta_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_receta);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Crear receta
    public function crear($datos, $usuario_id) {
        $sql = "INSERT INTO recetas (titulo, duracion, dificultad, origen, tipo, preparacion, imagen_url, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sisssssi", $datos['titulo'], $datos['duracion'], $datos['dificultad'], $datos['origen'], $datos['tipo'], $datos['preparacion'], $datos['imagen'], $usuario_id);
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    // Guardar ingredientes
    public function guardarIngredientes($receta_id, $nombres, $cantidades) {
        $sql = "INSERT INTO ingredientes (receta_id, nombre, cantidad) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $guardado = true;
        
        for ($i = 0; $i < count($nombres); $i++) {
            if (!empty($nombres[$i])) {
                $cantidad = $cantidades[$i] ?? '';
                $stmt->bind_param("iss", $receta_id, $nombres[$i], $cantidad);
                if (!$stmt->execute()) {
                    $guardado = false;
                    break;
                }
            }
        }

        return $guardado;
    }

    // Actualizar receta
    public function actualizar($id, $datos) {
        if (!empty($datos['imagen'])) {
            $sql = "UPDATE recetas SET titulo=?, duracion=?, dificultad=?, origen=?, tipo=?, preparacion=?, imagen_url=? WHERE id=?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sisssssi", $datos['titulo'], $datos['duracion'], $datos['dificultad'], $datos['origen'], $datos['tipo'], $datos['preparacion'], $datos['imagen'], $id);
        } else {
            $sql = "UPDATE recetas SET titulo=?, duracion=?, dificultad=?, origen=?, tipo=?, preparacion=? WHERE id=?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sissssi", $datos['titulo'], $datos['duracion'], $datos['dificultad'], $datos['origen'], $datos['tipo'], $datos['preparacion'], $id);
        }
        return $stmt->execute();
    }

    // Eliminar ingredientes
    public function borrarIngredientes($receta_id) {
        $stmt = $this->db->prepare("DELETE FROM ingredientes WHERE receta_id = ?");
        $stmt->bind_param("i", $receta_id);
        return $stmt->execute();
    }

    // Eliminar receta
    public function borrar($id) {
        $this->borrarIngredientes($id);
        $stmt = $this->db->prepare("DELETE FROM recetas WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Obtener favoritos con paginación
    public function obtenerFavoritos($usuario_id, $limit = 9, $offset = 0) {
        $sql = "SELECT r.*, u.nombre as nombre_autor 
                FROM recetas r 
                INNER JOIN favoritos f ON r.id = f.receta_id 
                INNER JOIN usuarios u ON r.usuario_id = u.id 
                WHERE f.usuario_id = ? LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $usuario_id, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener solo los IDs de recetas favoritas de un usuario
    public function obtenerIdsFavoritos($usuario_id) {
        $sql = "SELECT receta_id FROM favoritos WHERE usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['receta_id'];
        }

        return $ids;
    }

    // Contar total de favoritos
    public function contarFavoritos($usuario_id) {
        $sql = "SELECT COUNT(*) as total FROM favoritos WHERE usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res['total'];
    }

    public function contarRecetasPorUsuario($usuario_id) {
        $sql = "SELECT COUNT(*) as total FROM recetas WHERE usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res['total'];
    }

    public function obtenerTodasParaModeracion($limit, $offset) {
        $sql = "SELECT r.*, u.nombre AS nombre_autor
                FROM recetas r
                JOIN usuarios u ON r.usuario_id = u.id
                ORDER BY r.id DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function contarTodasParaModeracion() {
        $sql = "SELECT COUNT(*) as total FROM recetas";
        $res = $this->db->query($sql)->fetch_assoc();
        return $res['total'];
    }

    // Orígenes en uso
    public function obtenerOrigenesEnUso() {
        if ($this->cacheOrigenes !== null) {
            return $this->cacheOrigenes;
        }

        $sql = "SELECT DISTINCT origen FROM recetas WHERE origen IS NOT NULL AND origen != '' ORDER BY origen ASC";
        $this->cacheOrigenes = $this->db->query($sql);
        return $this->cacheOrigenes;
    }

    // Tipos en uso
    public function obtenerTiposEnUso() {
        if ($this->cacheTipos !== null) {
            return $this->cacheTipos;
        }

        $sql = "SELECT DISTINCT tipo FROM recetas WHERE tipo IS NOT NULL AND tipo != '' ORDER BY tipo ASC";
        $this->cacheTipos = $this->db->query($sql);
        return $this->cacheTipos;
    }

    // Usuarios con recetas
    public function obtenerUsuariosConRecetas() {
        if ($this->cacheUsuarios !== null) {
            return $this->cacheUsuarios;
        }

        $sql = "SELECT DISTINCT u.id, u.nombre 
                FROM usuarios u 
                INNER JOIN recetas r ON u.id = r.usuario_id 
                ORDER BY u.nombre ASC";
        $this->cacheUsuarios = $this->db->query($sql);
        return $this->cacheUsuarios;
    }
}
