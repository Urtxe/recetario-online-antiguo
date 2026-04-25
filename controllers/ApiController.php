<?php
require_once 'services/RecipeService.php';
require_once 'config/helpers.php';
require_once 'controllers/BaseController.php';

class ApiController extends BaseController {
    private $recipeService;

    public function __construct($recipeService = null) {
        $this->recipeService = $recipeService ?: new RecipeService();
    }

    private function verificarSesion() {
        if (!isset($_SESSION['usuario_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
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
            'ingredientes' => $_GET['ingredientes'] ?? '',
            'orden' => $_GET['orden'] ?? 'fecha'
        ];
    }

    public function recetas() {
        $this->verificarSesion();

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
                'imagen_src' => recipe_image_url($receta['imagen_url'] ?? ''),
                'nombre_autor' => $receta['nombre_autor'],
                'usuario_id' => (int)$receta['usuario_id'],
                'favorite_count' => (int)($receta['favorite_count'] ?? 0),
                'es_favorito' => isset($listado['favoritos_ids'][(int)$receta['id']])
            ];
        }

        $this->jsonResponse([
            'success' => true,
            'data' => [
                'recetas' => $resultados,
                'pagina' => $pagina,
                'total_paginas' => $listado['total_paginas'],
                'total_recetas' => (int)$listado['total_recetas'],
                'usuario_id_actual' => (int)$_SESSION['usuario_id']
            ]
        ]);
    }

    public function receta() {
        $this->verificarSesion();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID de receta no válido'], 400);
        }

        $detalle = $this->recipeService->detailData($id, $_SESSION['usuario_id']);
        if (!$detalle['success']) {
            $this->jsonResponse(['success' => false, 'message' => $detalle['error']], 404);
        }

        $this->jsonResponse(['success' => true, 'data' => $detalle['receta'] + ['ingredientes' => $detalle['ingredientes'], 'es_favorito' => $detalle['es_favorito']]]);
    }
}
