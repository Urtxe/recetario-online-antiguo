<?php
require_once 'models/Receta.php';
require_once 'config/helpers.php';

class ApiController {
    private function verificarSesion() {
        if (!isset($_SESSION['usuario_id'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit();
        }
    }

    private function obtenerFiltros() {
        return [
            'buscar' => $_GET['buscar'] ?? '',
            'origen' => $_GET['origen'] ?? '',
            'dificultad' => $_GET['dificultad'] ?? '',
            'duracion' => $_GET['duracion'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'usuario' => $_GET['usuario'] ?? '',
            'ingredientes' => $_GET['ingredientes'] ?? ''
        ];
    }

    public function recetas() {
        $this->verificarSesion();

        header('Content-Type: application/json; charset=utf-8');

        $filtros = $this->obtenerFiltros();
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 12;
        $offset = ($pagina - 1) * $por_pagina;

        $modelo = new Receta();
        $recetas = $modelo->listarConFiltros($filtros, $por_pagina, $offset);
        $total_recetas = $modelo->contarTotal($filtros);
        $total_paginas = (int)ceil($total_recetas / $por_pagina);
        $favoritos_ids = array_fill_keys($modelo->obtenerIdsFavoritos($_SESSION['usuario_id']), true);
        $resultados = [];

        while ($receta = $recetas->fetch_assoc()) {
            $resultados[] = [
                'id' => (int)$receta['id'],
                'titulo' => $receta['titulo'],
                'duracion' => (int)$receta['duracion'],
                'dificultad' => $receta['dificultad'],
                'origen' => $receta['origen'],
                'tipo' => $receta['tipo'],
                'imagen_url' => $receta['imagen_url'],
                'nombre_autor' => $receta['nombre_autor'],
                'usuario_id' => (int)$receta['usuario_id'],
                'es_favorito' => isset($favoritos_ids[(int)$receta['id']])
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'recetas' => $resultados,
                'pagina' => $pagina,
                'total_paginas' => $total_paginas,
                'total_recetas' => (int)$total_recetas,
                'usuario_id_actual' => (int)$_SESSION['usuario_id']
            ]
        ]);
        exit();
    }

    public function receta() {
        $this->verificarSesion();

        header('Content-Type: application/json; charset=utf-8');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de receta no válido']);
            exit();
        }

        $modelo = new Receta();
        $receta = $modelo->obtenerPorId($id);

        if (!$receta) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Receta no encontrada']);
            exit();
        }

        $ingredientes = [];
        $resultadoIngredientes = $modelo->obtenerIngredientes($id);
        while ($ing = $resultadoIngredientes->fetch_assoc()) {
            $ingredientes[] = [
                'nombre' => $ing['nombre'],
                'cantidad' => $ing['cantidad']
            ];
        }

        $favoritos_ids = array_fill_keys($modelo->obtenerIdsFavoritos($_SESSION['usuario_id']), true);

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => (int)$receta['id'],
                'titulo' => $receta['titulo'],
                'duracion' => (int)$receta['duracion'],
                'dificultad' => $receta['dificultad'],
                'origen' => $receta['origen'],
                'tipo' => $receta['tipo'],
                'imagen_url' => $receta['imagen_url'],
                'nombre_autor' => $receta['nombre_autor'],
                'usuario_id' => (int)$receta['usuario_id'],
                'preparacion' => $receta['preparacion'],
                'ingredientes' => $ingredientes,
                'es_favorito' => isset($favoritos_ids[$id])
            ]
        ]);
        exit();
    }
}
