<div class="main-container">
    <h2>Mis Favoritos</h2>
    <div id="contenedor-grid">
        <?php include 'views/recetas/grid.php'; ?>
    </div>

    <?php if ($total_paginas > 1): ?>
    <div class="paginacion">
        <?php for($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="index.php?controller=receta&action=misFavoritos&pagina=<?php echo $i; ?>" 
               class="<?php echo ($pagina == $i) ? 'active' : ''; ?>">
               <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>