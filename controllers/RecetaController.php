<?php
require_once 'models/Receta.php';
require_once 'models/Usuario.php';

class RecetaController {
    
    // Validar autenticación
    private function verificarSesion() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: index.php?controller=auth&action=login');
            exit();
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
            // Validar campos obligatorios
            $titulo = trim($_POST['titulo'] ?? '');
            $preparacion = trim($_POST['preparacion'] ?? '');
            $duracion = (int)($_POST['duracion'] ?? 0);

            if (empty($titulo) || empty($preparacion) || $duracion <= 0) {
                $_SESSION['mensaje_flash'] = 'Completa todos los campos obligatorios';
                $_SESSION['tipo_flash'] = 'error';
                header('Location: index.php?controller=receta&action=crear');
                exit();
            }

            // Procesar imagen
            $imagen = '';
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $permitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (in_array($_FILES['imagen']['type'], $permitidos)) {
                    $imagen = time() . '_' . $_FILES['imagen']['name'];
                    move_uploaded_file($_FILES['imagen']['tmp_name'], 'public/uploads/' . $imagen);
                } else {
                    $_SESSION['mensaje_flash'] = 'Formato de imagen no válido';
                    $_SESSION['tipo_flash'] = 'error';
                    header('Location: index.php?controller=receta&action=crear');
                    exit();
                }
            }

            // Guardar en BD
            $datos = [
                'titulo' => $titulo,
                'duracion' => $duracion,
                'dificultad' => $_POST['dificultad'],
                'origen' => $_POST['origen'],
                'tipo' => $_POST['tipo'],
                'preparacion' => $preparacion,
                'imagen' => $imagen
            ];

            $modelo = new Receta();
            $id_receta = $modelo->crear($datos, $_SESSION['usuario_id']);

            if ($id_receta) {
                $modelo->guardarIngredientes($id_receta, $_POST['ingrediente_nombre'], $_POST['ingrediente_cantidad']);
                $_SESSION['mensaje_flash'] = '¡Receta guardada!';
                $_SESSION['tipo_flash'] = 'success';
                header('Location: index.php');
                exit();
            } else {
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
            // Gestionar imagen
            $nombre_imagen = $receta['imagen_url'];
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $permitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (in_array($_FILES['imagen']['type'], $permitidos)) {
                    $nombre_imagen = time() . '_' . $_FILES['imagen']['name'];
                    move_uploaded_file($_FILES['imagen']['tmp_name'], 'public/uploads/' . $nombre_imagen);
                } else {
                    $_SESSION['mensaje_flash'] = 'Formato de imagen no válido';
                    $_SESSION['tipo_flash'] = 'error';
                    header('Location: index.php?controller=receta&action=editar&id=' . $id);
                    exit();
                }
            }

            $datos = [
                'titulo'      => trim($_POST['titulo']),
                'duracion'    => (int)$_POST['duracion'],
                'dificultad'  => $_POST['dificultad'],
                'origen'      => $_POST['origen'],
                'tipo'        => $_POST['tipo'],
                'preparacion' => trim($_POST['preparacion']),
                'imagen'      => $nombre_imagen
            ];

            if ($modelo->actualizar($id, $datos)) {
                $modelo->borrarIngredientes($id);
                $modelo->guardarIngredientes($id, $_POST['ingrediente_nombre'], $_POST['ingrediente_cantidad']);
                $_SESSION['mensaje_flash'] = '¡Receta actualizada!';
                $_SESSION['tipo_flash'] = 'success';
            } else {
                $_SESSION['mensaje_flash'] = 'Error al actualizar la receta';
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
        $modelo = new Receta();
        $receta = $modelo->obtenerPorId($id);

        if ($receta['usuario_id'] == $_SESSION['usuario_id']) {
            $modelo->borrar($id);
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
        $receta_id = $_GET['id'] ?? null;
        $usuario_id = $_SESSION['usuario_id'];

        if ($receta_id) {
            $uModel = new Usuario();
            $resultado = $uModel->toggleFavorito($usuario_id, $receta_id);
            echo json_encode(['success' => $resultado]);
        }
        exit();
    }
}
?>