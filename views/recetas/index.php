<div class="content-card" id="recetas">
    <h2>Recetas</h2>
    <?php require 'views/recetas/_filters.php'; ?>
    
    <div id="contenedor-grid">
        <?php require 'views/recetas/grid.php'; ?>
        <?php
        $params = $_GET;
        unset($params['pagina']);
        $pagination_base_url = 'index.php?' . http_build_query($params);
        require 'views/recetas/_pagination.php';
        ?>
    </div>
</div>
