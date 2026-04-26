<div class="content-card">
    <h2>Mis Favoritos</h2>
    <div id="contenedor-grid">
        <?php include 'views/recetas/grid.php'; ?>
        <?php
        $pagination_base_url = 'index.php?controller=receta&action=misFavoritos';
        require 'views/recetas/_pagination.php';
        ?>
    </div>
</div>
