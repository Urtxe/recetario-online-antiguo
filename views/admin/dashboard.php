<div class="content-card">
    <h2>Moderación</h2>

    <div class="section-panel">
        <p><strong>Total recetas:</strong> <?php echo (int)$totalRecetas; ?></p>
        <p><strong>Total usuarios:</strong> <?php echo (int)$totalUsuarios; ?></p>
    </div>

    <div class="recetas-grid-container">
        <?php if ($recetas && $recetas->num_rows > 0): ?>
            <?php while ($receta = $recetas->fetch_assoc()): ?>
                <?php
                    $mostrarFavorito = false;
                    ob_start();
                    ?>
                    <form action="index.php?controller=admin&action=borrarReceta&id=<?php echo (int)$receta['id']; ?>" method="post" class="inline-form" data-confirm="¿Eliminar como moderación?">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn-delete-mini">Borrar</button>
                    </form>
                    <?php
                    $accionesSecundariasHtml = ob_get_clean();
                    include __DIR__ . '/../recetas/_recipe_card.php';
                ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-state">No hay recetas para moderar</p>
        <?php endif; ?>
    </div>

    <?php if ($total_paginas > 1): ?>
        <div class="paginacion">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="index.php?controller=admin&action=dashboard&pagina=<?php echo $i; ?>" class="<?php echo ($pagina == $i) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
