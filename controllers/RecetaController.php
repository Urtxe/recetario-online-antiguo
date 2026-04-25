<?php
require_once 'models/Receta.php';
require_once 'services/RecipeService.php';
require_once 'services/ImageStorageService.php';
require_once 'config/helpers.php';
require_once 'controllers/BaseController.php';

class RecetaController extends BaseController {
    private $recipeService;
    private $imageStorageService;

    private $dificultadesPermitidas = ['facil', 'medio', 'dificil'];
    private $origenesPermitidos = [
        'euskalduna', 'española', 'italiana', 'francesa', 'europea',
        'nordica', 'africa', 'asia', 'latina', 'caribeña',
        'norteamericana', 'fusión', 'europa del este', 'escandinava'
    ];
    private $tiposPermitidos = [
        'desayuno', 'aperitivo', 'entrante', 'principal',
        'postre', 'merienda', 'guarnición', 'salsa'
    ];

    public function __construct($recipeService = null) {
        $this->recipeService = $recipeService ?: new RecipeService();
        $this->imageStorageService = new ImageStorageService();
    }

    private function verificarSesion() {
        $this->requireLogin();
    }

    private function validarRecetaDesdePost() {
        $titulo = trim($_POST['titulo'] ?? '');
        $preparacion = trim($_POST['preparacion'] ?? '');
        $duracion = (int)($_POST['duracion'] ?? 0);
        $dificultad = $_POST['dificultad'] ?? '';
        $origen = $_POST['origen'] ?? '';
        $tipo = $_POST['tipo'] ?? '';

        if ($titulo === '' || $preparacion === '' || $duracion <= 0) {
            return [false, 'Completa todos los campos obligatorios', null];
        }

        if (!in_array($dificultad, $this->dificultadesPermitidas, true)) {
            return [false, 'La dificultad seleccionada no es válida', null];
        }

        if (!in_array($origen, $this->origenesPermitidos, true)) {
            return [false, 'El origen seleccionado no es válido', null];
        }

        if (!in_array($tipo, $this->tiposPermitidos, true)) {
            return [false, 'El tipo seleccionado no es válido', null];
        }

        return [true, '', [
            'titulo' => $titulo,
            'preparacion' => $preparacion,
            'duracion' => $duracion,
            'dificultad' => $dificultad,
            'origen' => $origen,
            'tipo' => $tipo
        ]];
    }

    private function eliminarImagenSiExiste($nombreImagen) {
        $this->imageStorageService->deleteImage($nombreImagen);
    }

    public function index() {
        $this->verificarSesion();
        $usuario_nombre = $_SESSION['usuario_nombre'];

        $filtros = [
            'buscar' => $_GET['buscar'] ?? '',
            'origen' => $_GET['origen'] ?? '',
            'dificultad' => $_GET['dificultad'] ?? '',
            'duracion' => $_GET['duracion'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'usuario' => $_GET['usuario'] ?? '',
            'ingredientes' => $_GET['ingredientes'] ?? ''
        ];

        $pagina = max(1, isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1);
        $por_pagina = 12;

        $listado = $this->recipeService->listData($filtros, $pagina, $por_pagina, $_SESSION['usuario_id']);

        $modelo = new Receta();
        $lista_origenes = $modelo->obtenerOrigenesEnUso();
        $lista_tipos = $modelo->obtenerTiposEnUso();
        $lista_usuarios = $modelo->obtenerUsuariosConRecetas();
        $recetas = $listado['recetas'];
        $favoritos_ids = $listado['favoritos_ids'];
        $total_recetas = $listado['total_recetas'];
        $total_paginas = $listado['total_paginas'];

        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            require_once 'views/recetas/grid.php';
            $params = $_GET;
            unset($params['pagina'], $params['ajax']);
            $query_filtros = http_build_query($params);
            echo '<div class="paginacion">';
            for ($i = 1; $i <= $total_paginas; $i++) {
                $clase = ($pagina == $i) ? ' class="active"' : '';
                echo "<a href=\"index.php?{$query_filtros}&pagina={$i}\"{$clase}>{$i}</a>";
            }
            echo '</div>';
            return;
        }

        require_once 'views/layouts/header.php';
        require_once 'views/recetas/index.php';
        require_once 'views/layouts/footer.php';
    }

    public function ver($id) {
        $this->verificarSesion();
        $usuario_nombre = $_SESSION['usuario_nombre'];
        $usuario_id_actual = $_SESSION['usuario_id'];

        $detalle = $this->recipeService->detailData($id, $usuario_id_actual);
        if (!$detalle['success']) {
            header('Location: index.php');
            exit();
        }

        $receta = $detalle['receta'];
        $ingredientes = $detalle['ingredientes'];
        $es_favorito = $detalle['es_favorito'];

        require_once 'views/layouts/header.php';
        require_once 'views/recetas/ver.php';
        require_once 'views/layouts/footer.php';
    }

    public function crear() {
        $this->verificarSesion();
        $usuario_nombre = $_SESSION['usuario_nombre'];

        require_once 'views/layouts/header.php';
        require_once 'views/recetas/crear.php';
        require_once 'views/layouts/footer.php';
    }

    public function guardar() {
        $this->verificarSesion();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit();
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');
        list($valido, $mensajeError, $datosBase) = $this->validarRecetaDesdePost();
        if (!$valido) {
            $_SESSION['mensaje_flash'] = $mensajeError;
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=receta&action=crear');
            exit();
        }

        list($imagenOk, $imagen, $errorImagen) = $this->imageStorageService->storeUploadedImage($_FILES['imagen'] ?? null);
        if (!$imagenOk) {
            $_SESSION['mensaje_flash'] = $errorImagen;
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=receta&action=crear');
            exit();
        }

        $datosBase['imagen'] = $imagen;
        $datosBase['ingrediente_nombre'] = $_POST['ingrediente_nombre'] ?? [];
        $datosBase['ingrediente_cantidad'] = $_POST['ingrediente_cantidad'] ?? [];

        $resultado = $this->recipeService->createRecipe($datosBase, $_SESSION['usuario_id']);
        if (!$resultado['success']) {
            $this->eliminarImagenSiExiste($imagen);
            $_SESSION['mensaje_flash'] = $resultado['error'];
            $_SESSION['tipo_flash'] = 'error';
            header('Location: index.php?controller=receta&action=crear');
            exit();
        }

        security_log('recipe_created', ['recipe_id' => (int)$resultado['id'], 'user_id' => (int)$_SESSION['usuario_id']], 'info');
        $_SESSION['mensaje_flash'] = '¡Receta guardada!';
        $_SESSION['tipo_flash'] = 'success';
        header('Location: index.php');
        exit();
    }

    public function editar($id) {
        $this->verificarSesion();
        $modelo = new Receta();
        $receta = $modelo->obtenerPorId($id);

        if (!$receta || $receta['usuario_id'] != $_SESSION['usuario_id']) {
            header('Location: index.php');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf_token($_POST['_csrf_token'] ?? '');
            list($valido, $mensajeError, $datosBase) = $this->validarRecetaDesdePost();
            if (!$valido) {
                $_SESSION['mensaje_flash'] = $mensajeError;
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=receta&action=editar&id=' . $id);
                exit();
            }

            list($imagenOk, $nombreImagen, $errorImagen) = $this->imageStorageService->storeUploadedImage($_FILES['imagen'] ?? null);
            if (!$imagenOk) {
                $_SESSION['mensaje_flash'] = $errorImagen;
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=receta&action=editar&id=' . $id);
                exit();
            }

            $datosBase['imagen'] = $nombreImagen ?: $receta['imagen_url'];
            $datosBase['ingrediente_nombre'] = $_POST['ingrediente_nombre'] ?? [];
            $datosBase['ingrediente_cantidad'] = $_POST['ingrediente_cantidad'] ?? [];

            $resultado = $this->recipeService->updateRecipe($id, $datosBase, $_SESSION['usuario_id']);
            if (!$resultado['success']) {
                if (!empty($nombreImagen)) {
                    $this->eliminarImagenSiExiste($nombreImagen);
                }
                $_SESSION['mensaje_flash'] = $resultado['error'];
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=receta&action=editar&id=' . $id);
                exit();
            }

            if (!empty($nombreImagen) && $nombreImagen !== ($resultado['imagen_anterior'] ?? '')) {
                $this->eliminarImagenSiExiste($resultado['imagen_anterior'] ?? '');
            }

            security_log('recipe_updated', ['recipe_id' => (int)$id, 'user_id' => (int)$_SESSION['usuario_id']], 'info');
            $_SESSION['mensaje_flash'] = '¡Receta actualizada!';
            $_SESSION['tipo_flash'] = 'success';
            header('Location: index.php?controller=receta&action=ver&id=' . $id);
            exit();
        }

        $ingredientes = [];
        $resultadoIngredientes = $modelo->obtenerIngredientes($id);
        while ($ing = $resultadoIngredientes->fetch_assoc()) {
            $ingredientes[] = $ing;
        }

        require_once 'views/layouts/header.php';
        require_once 'views/recetas/editar.php';
        require_once 'views/layouts/footer.php';
    }

    public function actualizar() {
        $this->verificarSesion();
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_receta'])) {
            $this->editar($_POST['id_receta']);
        } else {
            $this->redirect('index.php');
        }
    }

    public function borrar($id) {
        $this->verificarSesion();
        $this->requirePost();

        require_csrf_token($_POST['_csrf_token'] ?? '');
        $resultado = $this->recipeService->deleteRecipe($id, $_SESSION['usuario_id']);
        if ($resultado['success']) {
            $this->eliminarImagenSiExiste($resultado['receta']['imagen_url'] ?? '');
            security_log('recipe_deleted', ['recipe_id' => (int)$id], 'warning');
        } else {
            $_SESSION['mensaje_flash'] = 'Error al borrar la receta';
            $_SESSION['tipo_flash'] = 'error';
            security_log('recipe_delete_failed', ['recipe_id' => (int)$id], 'warning');
        }

        header('Location: index.php');
        exit();
    }

    public function misFavoritos() {
        $this->verificarSesion();
        $usuario_nombre = $_SESSION['usuario_nombre'];

        $pagina = max(1, isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1);
        $por_pagina = 9;
        $listado = $this->recipeService->getFavoritesData($_SESSION['usuario_id'], $pagina, $por_pagina);

        $recetas = $listado['recetas'];
        $favoritos_ids = $listado['favoritos_ids'];
        $total_favoritos = $listado['total_favoritos'];
        $total_paginas = $listado['total_paginas'];
        $titulo_pagina = 'Mis Favoritos';
        $desde_favoritos = true;

        require_once 'views/layouts/header.php';
        require_once 'views/recetas/mis_favoritos.php';
        require_once 'views/layouts/footer.php';
    }

    public function favorito() {
        $this->verificarSesion();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit();
        }

        require_csrf_token($_POST['_csrf_token'] ?? '');
        $receta_id = $_POST['id'] ?? null;
        $usuario_id = $_SESSION['usuario_id'];

        if ($receta_id) {
            $this->jsonResponse($this->recipeService->toggleFavorite($usuario_id, $receta_id));
        }
        exit();
    }
}
?>
