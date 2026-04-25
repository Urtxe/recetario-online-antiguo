<div class="content-card">
    <h2>Moderación</h2>

    <div class="section-panel">
        <p><strong>Total recetas:</strong> <?php echo (int)$totalRecetas; ?></p>
        <p><strong>Total usuarios:</strong> <?php echo (int)$totalUsuarios; ?></p>
    </div>

    <div class="recetas-grid-container">
        <?php if ($recetas && $recetas->num_rows > 0): ?>
            <?php while ($receta = $recetas->fetch_assoc()): ?>
                <div class="receta-item">
                    <a href="index.php?controller=receta&action=ver&id=<?php echo $receta['id']; ?>" class="link-estirado" aria-label="<?php echo htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (!empty($receta['imagen_url'])): ?>
                            <img src="<?php echo htmlspecialchars(recipe_image_url($receta['imagen_url']), ENT_QUOTES, 'UTF-8'); ?>" class="receta-miniatura" alt="<?php echo htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php else: ?>
                            <div class="sin-imagen-placeholder"><span>Imagen no subida</span></div>
                        <?php endif; ?>
                        <div class="recipe-card-body">
                            <h3 class="receta-titulo"><?php echo htmlspecialchars($receta['titulo']); ?></h3>
                            <p class="recipe-card-meta">
                                <strong>Autor:</strong> <?php echo htmlspecialchars($receta['nombre_autor']); ?><br>
                                <strong>Origen:</strong> <?php echo htmlspecialchars($receta['origen']); ?><br>
                                <strong>Tipo:</strong> <?php echo htmlspecialchars($receta['tipo']); ?>
                            </p>
                        </div>
                    </a>

                    <div class="grid-admin-buttons">
                        <form action="index.php?controller=admin&action=borrarReceta&id=<?php echo $receta['id']; ?>" method="post" class="inline-form" data-confirm="¿Eliminar como moderación?">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn-delete-mini">Borrar</button>
                        </form>
                    </div>
                </div>
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
