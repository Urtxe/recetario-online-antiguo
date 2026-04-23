<?php

require_once 'models/Receta.php';
require_once 'models/Usuario.php';

class RecipeService {
    private $recetaModel;
    private $usuarioModel;

    public function __construct($recetaModel = null, $usuarioModel = null) {
        $this->recetaModel = $recetaModel ?: new Receta();
        $this->usuarioModel = $usuarioModel ?: new Usuario();
    }

    public function listData($filtros, $pagina, $porPagina, $usuarioActualId) {
        $offset = ($pagina - 1) * $porPagina;
        $recetas = $this->recetaModel->listarConFiltros($filtros, $porPagina, $offset);
        $totalRecetas = $this->recetaModel->contarTotal($filtros);
        $totalPaginas = (int)ceil($totalRecetas / $porPagina);
        $favoritosIds = array_fill_keys($this->recetaModel->obtenerIdsFavoritos($usuarioActualId), true);

        return [
            'recetas' => $recetas,
            'total_recetas' => $totalRecetas,
            'total_paginas' => $totalPaginas,
            'favoritos_ids' => $favoritosIds
        ];
    }

    public function detailData($id, $usuarioActualId) {
        $receta = $this->recetaModel->obtenerPorId($id);
        if (!$receta) {
            return ['success' => false, 'error' => 'Receta no encontrada'];
        }

        $ingredientesResult = $this->recetaModel->obtenerIngredientes($id);
        $ingredientes = [];
        while ($ing = $ingredientesResult->fetch_assoc()) {
            $ingredientes[] = $ing;
        }

        return [
            'success' => true,
            'receta' => $receta,
            'ingredientes' => $ingredientes,
            'es_favorito' => $this->usuarioModel->esFavorito($usuarioActualId, $id)
        ];
    }

    public function createRecipe($datos, $usuarioId) {
        $this->recetaModel->iniciarTransaccion();
        try {
            $idReceta = $this->recetaModel->crear($datos, $usuarioId);
            if (!$idReceta || !$this->recetaModel->guardarIngredientes($idReceta, $datos['ingrediente_nombre'] ?? [], $datos['ingrediente_cantidad'] ?? [])) {
                throw new Exception('Error al guardar');
            }
            $this->recetaModel->confirmarTransaccion();
            return ['success' => true, 'id' => $idReceta];
        } catch (Exception $e) {
            $this->recetaModel->revertirTransaccion();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateRecipe($id, $datos, $usuarioId) {
        $receta = $this->recetaModel->obtenerPorId($id);
        if (!$receta || (int)$receta['usuario_id'] !== (int)$usuarioId) {
            return ['success' => false, 'error' => 'No autorizado'];
        }

        $this->recetaModel->iniciarTransaccion();
        try {
            if (!$this->recetaModel->actualizar($id, $datos)) {
                throw new Exception('Error al actualizar la receta');
            }

            if (!$this->recetaModel->borrarIngredientes($id)) {
                throw new Exception('Error al limpiar ingredientes');
            }

            if (!$this->recetaModel->guardarIngredientes($id, $datos['ingrediente_nombre'] ?? [], $datos['ingrediente_cantidad'] ?? [])) {
                throw new Exception('Error al guardar ingredientes');
            }

            $this->recetaModel->confirmarTransaccion();
            return ['success' => true, 'imagen_anterior' => $receta['imagen_url'] ?? ''];
        } catch (Exception $e) {
            $this->recetaModel->revertirTransaccion();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteRecipe($id, $usuarioId, $force = false) {
        $receta = $this->recetaModel->obtenerPorId($id);
        if (!$receta || (!$force && (int)$receta['usuario_id'] !== (int)$usuarioId)) {
            return ['success' => false, 'error' => 'No autorizado', 'receta' => $receta];
        }

        $this->recetaModel->iniciarTransaccion();
        try {
            if (!$this->recetaModel->borrar($id)) {
                throw new Exception('No se pudo borrar la receta');
            }
            $this->recetaModel->confirmarTransaccion();
            return ['success' => true, 'receta' => $receta];
        } catch (Exception $e) {
            $this->recetaModel->revertirTransaccion();
            return ['success' => false, 'error' => $e->getMessage(), 'receta' => $receta];
        }
    }

    public function toggleFavorite($userId, $recipeId) {
        $resultado = $this->usuarioModel->toggleFavorito($userId, $recipeId);
        return [
            'success' => $resultado,
            'favorito' => $this->usuarioModel->esFavorito($userId, $recipeId)
        ];
    }

    public function getFavoritesData($usuarioId, $pagina, $porPagina) {
        $offset = ($pagina - 1) * $porPagina;
        $recetas = $this->recetaModel->obtenerFavoritos($usuarioId, $porPagina, $offset);
        $totalFavoritos = $this->recetaModel->contarFavoritos($usuarioId);
        $totalPaginas = (int)ceil($totalFavoritos / $porPagina);
        $favoritosIds = array_fill_keys($this->recetaModel->obtenerIdsFavoritos($usuarioId), true);

        return [
            'recetas' => $recetas,
            'total_favoritos' => $totalFavoritos,
            'total_paginas' => $totalPaginas,
            'favoritos_ids' => $favoritosIds
        ];
    }

    public function getPendingModerationData($pagina, $porPagina) {
        $offset = ($pagina - 1) * $porPagina;
        $recetas = $this->recetaModel->obtenerTodasParaModeracion($porPagina, $offset);
        $total = $this->recetaModel->contarTodasParaModeracion();
        return [
            'recetas' => $recetas,
            'total' => $total,
            'total_paginas' => (int)ceil($total / $porPagina)
        ];
    }

    public function adminDeleteRecipe($id) {
        return $this->deleteRecipe($id, 0, true);
    }
}
