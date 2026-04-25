<?php
require_once 'models/Receta.php';
require_once 'services/RecipeService.php';
require_once 'config/helpers.php';
require_once 'controllers/BaseController.php';

class RecetaController extends BaseController {
    private $recipeService;

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

    private function procesarImagenSubida($archivo) {
        if (!isset($archivo) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return [true, '', null];
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            security_log('upload_failed', ['reason' => 'upload_error', 'code' => $archivo['error']], 'warning');
            return [false, '', 'Error al subir la imagen'];
        }

        $maximoBytes = 2 * 1024 * 1024;
        if ($archivo['size'] > $maximoBytes) {
            security_log('upload_failed', ['reason' => 'size_limit', 'size' => $archivo['size']], 'warning');
            return [false, '', 'La imagen supera el tamaño máximo permitido'];
        }

        $mimePermitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];

        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeReal = $finfo->file($archivo['tmp_name']);
        } elseif (function_exists('mime_content_type')) {
            $mimeReal = mime_content_type($archivo['tmp_name']);
        } else {
            $mimeReal = '';
        }

        if (!isset($mimePermitidos[$mimeReal])) {
            security_log('upload_failed', ['reason' => 'mime', 'mime' => $mimeReal], 'warning');
            return [false, '', 'Formato de imagen no válido'];
        }

        $dimensions = @getimagesize($archivo['tmp_name']);
        if ($dimensions === false) {
            security_log('upload_failed', ['reason' => 'invalid_image'], 'warning');
            return [false, '', 'La imagen no es válida'];
        }

        if ($dimensions[0] > 4096 || $dimensions[1] > 4096) {
            security_log('upload_failed', ['reason' => 'dimensions', 'width' => $dimensions[0], 'height' => $dimensions[1]], 'warning');
            return [false, '', 'La imagen supera las dimensiones permitidas'];
        }

        if (!is_dir('public/uploads')) {
            mkdir('public/uploads', 0775, true);
        }

        $nombreImagen = bin2hex(random_bytes(16)) . '.' . $mimePermitidos[$mimeReal];
        $rutaDestino = 'public/uploads/' . $nombreImagen;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return [false, '', 'No se pudo guardar la imagen'];
        }

        return [true, $nombreImagen, null];
    }

    private function eliminarImagenSiExiste($nombreImagen) {
        if (!empty($nombreImagen)) {
            $ruta = 'public/uploads/' . $nombreImagen;
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
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

        list($imagenOk, $imagen, $errorImagen) = $this->procesarImagenSubida($_FILES['imagen'] ?? null);
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

            list($imagenOk, $nombreImagen, $errorImagen) = $this->procesarImagenSubida($_FILES['imagen'] ?? null);
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
