<div class="content-card recipe-card">
    <?php if (!empty($receta['imagen_url'])): ?>
        <img
            src="<?php echo htmlspecialchars(recipe_image_url($receta['imagen_url']), ENT_QUOTES, 'UTF-8'); ?>"
            id="img-zoom"
            class="recipe-main-image clickable"
            alt="<?php echo htmlspecialchars($receta['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
        >

        <div id="lightbox-modal" class="modal-overlay">
            <span class="close-modal">&times;</span>
            <div class="modal-content-wrapper">
                <img id="modal-img" src="" alt="Imagen ampliada">
            </div>
        </div>
    <?php endif; ?>

    <div class="recipe-info">
        <h2><?php echo htmlspecialchars($receta['titulo']); ?></h2>
        <p class="recipe-info-meta">
            <span class="recipe-author">Por: <?php echo htmlspecialchars($receta['nombre_autor']); ?></span> |
            Duración: <?php echo htmlspecialchars($receta['duracion']); ?> min |
            Dificultad: <?php echo htmlspecialchars($receta['dificultad']); ?> |
            Origen: <?php echo htmlspecialchars($receta['origen']); ?>
        </p>
    </div>

    <div class="recipe-detail-sections">
        <div class="recipe-section">
            <h3>Ingredientes</h3>
            <ul class="ingredient-list">
                <?php foreach ($ingredientes as $ing): ?>
                    <li>
                        <span class="ing-name"><?php echo htmlspecialchars($ing['nombre']); ?></span>
                        <span class="ing-qty"><?php echo htmlspecialchars($ing['cantidad']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="recipe-section">
            <h3>Elaboración / Preparación</h3>
            <p class="recipe-preparation">
                <?php echo nl2br(htmlspecialchars($receta['preparacion'])); ?>
            </p>
        </div>
    </div>

    <div class="recipe-detail-actions">
        <div class="recipe-detail-actions-group">
            <button id="btn-favorito" class="btn-fav btn-fav-ver recipe-detail-fav" data-id="<?php echo (int)$receta['id']; ?>" aria-label="<?php echo $es_favorito ? 'Quitar de favoritos' : 'Añadir a favoritos'; ?>">
                <?php echo $es_favorito ? '❤️' : '🤍'; ?>
            </button>
        </div>

        <?php if ($usuario_id_actual == $receta['usuario_id']): ?>
            <div class="recipe-detail-actions-group">
                <a href="index.php?controller=receta&action=editar&id=<?php echo (int)$receta['id']; ?>" class="btn-edit">Editar Receta</a>
                <form action="index.php?controller=receta&action=borrar&id=<?php echo (int)$receta['id']; ?>" method="post" class="inline-form" data-confirm="¿Seguro que quieres borrarla?">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$receta['id']; ?>">
                    <button type="submit" class="btn-delete">Borrar Receta</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
