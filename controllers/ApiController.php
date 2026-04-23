<?php
require_once 'services/RecipeService.php';
require_once 'config/helpers.php';

class ApiController {
    private $recipeService;

    public function __construct($recipeService = null) {
        $this->recipeService = $recipeService ?: new RecipeService();
    }

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
        $listado = $this->recipeService->listData($filtros, $pagina, $por_pagina, $_SESSION['usuario_id']);
        $recetas = $listado['recetas'];
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
                'es_favorito' => isset($listado['favoritos_ids'][(int)$receta['id']])
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'recetas' => $resultados,
                'pagina' => $pagina,
                'total_paginas' => $listado['total_paginas'],
                'total_recetas' => (int)$listado['total_recetas'],
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

        $detalle = $this->recipeService->detailData($id, $_SESSION['usuario_id']);
        if (!$detalle['success']) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $detalle['error']]);
            exit();
        }

        echo json_encode(['success' => true, 'data' => $detalle['receta'] + ['ingredientes' => $detalle['ingredientes'], 'es_favorito' => $detalle['es_favorito']]]);
        exit();
    }
}
