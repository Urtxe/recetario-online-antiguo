<?php
require_once 'models/Receta.php';
require_once 'models/Usuario.php';
require_once 'config/helpers.php';

class RecetaController {

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
    
    // Validar autenticación
    private function verificarSesion() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
        }
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
            return [false, '', 'Error al subir la imagen'];
        }

        $maximoBytes = 2 * 1024 * 1024;
        if ($archivo['size'] > $maximoBytes) {
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
            return [false, '', 'Formato de imagen no válido'];
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
        
        // Obtener filtros de URL
        $filtros = [
            'buscar' => $_GET['buscar'] ?? '',
            'origen' => $_GET['origen'] ?? '',
            'dificultad' => $_GET['dificultad'] ?? '',
            'duracion' => $_GET['duracion'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'usuario' => $_GET['usuario'] ?? '',
            'ingredientes' => $_GET['ingredientes'] ?? ''
        ];

        // Paginación
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 12;
        $offset = ($pagina - 1) * $por_pagina;

        $modelo = new Receta();
        
        // Cargar listas dinámicas
        $lista_origenes = $modelo->obtenerOrigenesEnUso();
        $lista_tipos = $modelo->obtenerTiposEnUso();
        $lista_usuarios = $modelo->obtenerUsuariosConRecetas();
        $favoritos_ids = array_fill_keys($modelo->obtenerIdsFavoritos($_SESSION['usuario_id']), true);

        // Búsqueda filtrada
        $recetas = $modelo->listarConFiltros($filtros, $por_pagina, $offset);
        $total_recetas = $modelo->contarTotal($filtros);
        $total_paginas = ceil($total_recetas / $por_pagina);

        // Responder AJAX o cargar vista completa
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            require_once 'views/recetas/grid.php';
            // Incluir paginación en la respuesta AJAX
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
        
        $modelo = new Receta();
        $receta = $modelo->obtenerPorId($id);
        $ingredientes = $modelo->obtenerIngredientes($id);

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
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_csrf_token($_POST['_csrf_token'] ?? '');
            list($valido, $mensajeError, $datosBase) = $this->validarRecetaDesdePost();
            if (!$valido) {
                $_SESSION['mensaje_flash'] = $mensajeError;
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=receta&action=crear');
                exit();
            }

            // Procesar imagen
            list($imagenOk, $imagen, $errorImagen) = $this->procesarImagenSubida($_FILES['imagen'] ?? null);
            if (!$imagenOk) {
                $_SESSION['mensaje_flash'] = $errorImagen;
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=receta&action=crear');
                exit();
            }

            $datosBase['imagen'] = $imagen;

            $modelo = new Receta();
            $modelo->iniciarTransaccion();

            try {
                $id_receta = $modelo->crear($datosBase, $_SESSION['usuario_id']);

                if (!$id_receta || !$modelo->guardarIngredientes($id_receta, $_POST['ingrediente_nombre'] ?? [], $_POST['ingrediente_cantidad'] ?? [])) {
                    throw new Exception('Error al guardar');
                }

                $modelo->confirmarTransaccion();
                $_SESSION['mensaje_flash'] = '¡Receta guardada!';
                $_SESSION['tipo_flash'] = 'success';
                header('Location: index.php');
                exit();
            } catch (Exception $e) {
                $modelo->revertirTransaccion();
                $this->eliminarImagenSiExiste($imagen);
                $_SESSION['mensaje_flash'] = 'Error al guardar';
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=receta&action=crear');
                exit();
            }
        }
    }

    public function editar($id) {
        $this->verificarSesion();
        $modelo = new Receta();
        $receta = $modelo->obtenerPorId($id);

        // Verificar autoría
        if (!$receta || $receta['usuario_id'] != $_SESSION['usuario_id']) {
            header('Location: index.php');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_csrf_token($_POST['_csrf_token'] ?? '');
            list($valido, $mensajeError, $datosBase) = $this->validarRecetaDesdePost();
            if (!$valido) {
                $_SESSION['mensaje_flash'] = $mensajeError;
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=receta&action=editar&id=' . $id);
                exit();
            }

            list($imagenOk, $nombre_imagen, $errorImagen) = $this->procesarImagenSubida($_FILES['imagen'] ?? null);
            if (!$imagenOk) {
                $_SESSION['mensaje_flash'] = $errorImagen;
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=receta&action=editar&id=' . $id);
                exit();
            }

            $imagenAnterior = $receta['imagen_url'];
            $datosBase['imagen'] = $nombre_imagen ?: $imagenAnterior;

            $modelo->iniciarTransaccion();

            try {
                if (!$modelo->actualizar($id, $datosBase)) {
                    throw new Exception('Error al actualizar la receta');
                }

                if (!$modelo->borrarIngredientes($id)) {
                    throw new Exception('Error al limpiar ingredientes');
                }

                if (!$modelo->guardarIngredientes($id, $_POST['ingrediente_nombre'] ?? [], $_POST['ingrediente_cantidad'] ?? [])) {
                    throw new Exception('Error al guardar ingredientes');
                }

                $modelo->confirmarTransaccion();
                if (!empty($nombre_imagen) && $nombre_imagen !== $imagenAnterior) {
                    $this->eliminarImagenSiExiste($imagenAnterior);
                }
                $_SESSION['mensaje_flash'] = '¡Receta actualizada!';
                $_SESSION['tipo_flash'] = 'success';
            } catch (Exception $e) {
                $modelo->revertirTransaccion();
                if (!empty($nombre_imagen)) {
                    $this->eliminarImagenSiExiste($nombre_imagen);
                }
                $_SESSION['mensaje_flash'] = $e->getMessage();
                $_SESSION['tipo_flash'] = 'error';
            }
            header('Location: index.php?controller=receta&action=ver&id=' . $id);
            exit();
        }

        $ingredientes = $modelo->obtenerIngredientes($id);
        require_once 'views/layouts/header.php';
        require_once 'views/recetas/editar.php';
        require_once 'views/layouts/footer.php';
    }

    public function actualizar() {
        // Redirige al método editar() unificado para mantener compatibilidad
        // con cualquier formulario que apunte a action=actualizar
        $this->verificarSesion();
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_receta'])) {
            $this->editar($_POST['id_receta']);
        } else {
            header('Location: index.php');
            exit();
        }
    }

    public function borrar($id) {
        $this->verificarSesion();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método no permitido');
        }
        require_csrf_token($_POST['_csrf_token'] ?? '');
        $modelo = new Receta();
        $receta = $modelo->obtenerPorId($id);

        if ($receta && $receta['usuario_id'] == $_SESSION['usuario_id']) {
            $modelo->iniciarTransaccion();
            try {
                if (!$modelo->borrar($id)) {
                    throw new Exception('No se pudo borrar la receta');
                }
                $modelo->confirmarTransaccion();
                $this->eliminarImagenSiExiste($receta['imagen_url'] ?? '');
            } catch (Exception $e) {
                $modelo->revertirTransaccion();
                $_SESSION['mensaje_flash'] = 'Error al borrar la receta';
                $_SESSION['tipo_flash'] = 'error';
            }
        }
        header('Location: index.php');
    }

    public function misFavoritos() {
        $this->verificarSesion();
        $usuario_nombre = $_SESSION['usuario_nombre'];
        
        // Paginación
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        $por_pagina = 9;
        $offset = ($pagina - 1) * $por_pagina;
        
        $modelo = new Receta();
        $recetas = $modelo->obtenerFavoritos($_SESSION['usuario_id'], $por_pagina, $offset);
        $favoritos_ids = array_fill_keys($modelo->obtenerIdsFavoritos($_SESSION['usuario_id']), true);
        $total_favoritos = $modelo->contarFavoritos($_SESSION['usuario_id']);
        $total_paginas = ceil($total_favoritos / $por_pagina);
        $titulo_pagina = 'Mis Favoritos';
        $desde_favoritos = true; // Variable para saber que estamos en favoritos

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
            $uModel = new Usuario();
            $resultado = $uModel->toggleFavorito($usuario_id, $receta_id);
            echo json_encode([
                'success' => $resultado,
                'favorito' => $uModel->esFavorito($usuario_id, $receta_id)
            ]);
        }
        exit();
    }
}
?>
