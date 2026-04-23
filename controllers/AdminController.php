<?php
require_once 'models/Receta.php';
require_once 'models/Usuario.php';
require_once 'services/RecipeService.php';
require_once 'config/helpers.php';

class AdminController {
    private $recipeService;
    private $usuarioModel;

    public function __construct($recipeService = null, $usuarioModel = null) {
        $this->recipeService = $recipeService ?: new RecipeService();
        $this->usuarioModel = $usuarioModel ?: new Usuario();
    }

    private function verificarAdmin() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }

        $usuario = $this->usuarioModel->obtenerPorIdConSeguridad($_SESSION['usuario_id']);
        if (!$usuario || !is_admin_user($usuario)) {
            http_response_code(403);
            exit('Acceso denegado');
        }
    }

    public function dashboard() {
        $this->verificarAdmin();

        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 15;
        $listado = $this->recipeService->getPendingModerationData($pagina, $por_pagina);

        $modelo = new Receta();
        $totalRecetas = $modelo->contarTodasParaModeracion();
        $totalUsuarios = $this->usuarioModel->contarUsuarios();

        $recetas = $listado['recetas'];
        $total_paginas = $listado['total_paginas'];

        require_once 'views/layouts/header.php';
        require_once 'views/admin/dashboard.php';
        require_once 'views/layouts/footer.php';
    }

    public function borrarReceta($id) {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método no permitido');
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');
        $resultado = $this->recipeService->adminDeleteRecipe($id);
        if (!$resultado['success']) {
            $_SESSION['mensaje_flash'] = 'No se pudo borrar la receta';
            $_SESSION['tipo_flash'] = 'error';
            security_log('admin_recipe_delete_failed', ['recipe_id' => (int)$id], 'warning');
        } else {
            $_SESSION['mensaje_flash'] = 'Receta moderada correctamente';
            $_SESSION['tipo_flash'] = 'success';
            security_log('admin_recipe_deleted', ['recipe_id' => (int)$id], 'warning');
        }

        header('Location: index.php?controller=admin&action=dashboard');
        exit();
    }
}
