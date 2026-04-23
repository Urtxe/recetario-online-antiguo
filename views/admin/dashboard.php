<div class="content-card">
    <h2>Moderación</h2>

    <div style="margin-bottom: 20px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
        <p><strong>Total recetas:</strong> <?php echo (int)$totalRecetas; ?></p>
        <p><strong>Total usuarios:</strong> <?php echo (int)$totalUsuarios; ?></p>
    </div>

    <div class="recetas-grid-container">
        <?php if ($recetas && $recetas->num_rows > 0): ?>
            <?php while ($receta = $recetas->fetch_assoc()): ?>
                <div class="receta-item">
                    <a href="index.php?controller=receta&action=ver&id=<?php echo $receta['id']; ?>" class="link-estirado">
                        <?php if (!empty($receta['imagen_url'])): ?>
                            <img src="public/uploads/<?php echo htmlspecialchars($receta['imagen_url']); ?>" class="receta-miniatura">
                        <?php else: ?>
                            <div class="sin-imagen-placeholder"><span>Imagen no subida</span></div>
                        <?php endif; ?>
                        <div style="padding: 15px; flex-grow: 1;">
                            <h3 class="receta-titulo"><?php echo htmlspecialchars($receta['titulo']); ?></h3>
                            <p style="font-size: 0.85rem; color: #666; margin: 8px 0;">
                                <strong>Autor:</strong> <?php echo htmlspecialchars($receta['nombre_autor']); ?><br>
                                <strong>Origen:</strong> <?php echo htmlspecialchars($receta['origen']); ?><br>
                                <strong>Tipo:</strong> <?php echo htmlspecialchars($receta['tipo']); ?>
                            </p>
                        </div>
                    </a>

                    <div class="grid-admin-buttons">
                        <form action="index.php?controller=admin&action=borrarReceta&id=<?php echo $receta['id']; ?>" method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar como moderación?')">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn-delete-mini">Borrar</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #777;">
                No hay recetas para moderar
            </p>
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
