<div class="content-card recipe-card">
    
    <?php if (!empty($receta['imagen_url'])): ?>
<img src="<?php echo htmlspecialchars(recipe_image_url($receta['imagen_url']), ENT_QUOTES, 'UTF-8'); ?>" 
     id="img-zoom" 
     class="recipe-main-image clickable" 
     alt="Click para ampliar">

<div id="lightbox-modal" class="modal-overlay">
    <span class="close-modal">&times;</span>
    <div class="modal-content-wrapper">
        <img id="modal-img" src="" alt="Imagen ampliada">
    </div>
</div>    <?php endif; ?>

    <div class="recipe-info">
        <h2><?php echo htmlspecialchars($receta['titulo']); ?></h2>
        <p class="recipe-info-meta">
            <span class="recipe-author">Por: <?php echo htmlspecialchars($receta['nombre_autor']); ?></span> | 
            Duración: <?php echo htmlspecialchars($receta['duracion']); ?> min | 
            Dificultad: <?php echo htmlspecialchars($receta['dificultad']); ?> |
            Origen: <?php echo htmlspecialchars($receta['origen']); ?>
        </p>
    </div>

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

    <?php if ($usuario_id_actual == $receta['usuario_id']): ?>
        <hr>
        <div class="receta-acciones-bottom" style="margin-top: 20px; text-align: center;">
            <a href="index.php?controller=receta&action=editar&id=<?php echo $receta['id']; ?>" class="btn-editar" style="background: #E67E22; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px; font-weight: bold;">Editar Receta</a>
            <form action="index.php?controller=receta&action=borrar&id=<?php echo $receta['id']; ?>" method="post" style="display:inline;" onsubmit="return confirm('¿Seguro que quieres borrarla?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo $receta['id']; ?>">
                <button type="submit" class="btn-borrar" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-weight: bold;">Borrar Receta</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="btn-fav-container">
        <button id="btn-favorito" class="btn-fav-ver" data-id="<?php echo $receta['id']; ?>">
            <?php echo $esFav ? '❤️' : '🤍'; ?>
        </button>
    </div>
    </div>

</div>
