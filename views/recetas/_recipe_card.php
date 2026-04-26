<?php
$receta = $receta ?? [];
$favoritos_ids = $favoritos_ids ?? [];
$mostrarFavorito = $mostrarFavorito ?? true;
$accionesSecundariasHtml = $accionesSecundariasHtml ?? '';
$desde_favoritos = !empty($desde_favoritos);

$recetaId = (int)($receta['id'] ?? 0);
$recetaUrl = 'index.php?controller=receta&action=ver&id=' . $recetaId;
$esFav = $mostrarFavorito && isset($favoritos_ids[$recetaId]);
$tieneImagen = !empty($receta['imagen_url']);
$imagenUrl = $tieneImagen ? recipe_image_url($receta['imagen_url']) : '';
?>

<article class="receta-item">
    <a href="<?php echo htmlspecialchars($recetaUrl, ENT_QUOTES, 'UTF-8'); ?>" class="recipe-card-link link-estirado" aria-label="<?php echo htmlspecialchars($receta['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <div class="recipe-card-media">
            <?php if ($tieneImagen): ?>
                <img
                    src="<?php echo htmlspecialchars($imagenUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    class="receta-miniatura"
                    alt="<?php echo htmlspecialchars($receta['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                >
            <?php else: ?>
                <div class="sin-imagen-placeholder"><span>Imagen no subida</span></div>
            <?php endif; ?>
        </div>

        <div class="recipe-card-body">
            <h3 class="receta-titulo"><?php echo htmlspecialchars($receta['titulo'] ?? 'Receta', ENT_QUOTES, 'UTF-8'); ?></h3>

            <p class="recipe-card-meta">
                <span><strong>Origen:</strong> <?php echo htmlspecialchars($receta['origen'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                <span><strong>Tipo:</strong> <?php echo htmlspecialchars($receta['tipo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                <span><strong>Tiempo:</strong> <?php echo htmlspecialchars((string)($receta['duracion'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?> min</span>
            </p>

            <div class="recipe-card-footer">
                <p class="recipe-author-label">Por: <?php echo htmlspecialchars($receta['nombre_autor'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if (!empty($receta['favorite_count'])): ?>
                    <span class="recipe-chip">Favoritos: <?php echo (int)$receta['favorite_count']; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>

    <div class="recipe-card-actions">
        <?php if ($mostrarFavorito): ?>
            <div class="btn-fav-container">
                <button
                    class="btn-fav"
                    data-id="<?php echo $recetaId; ?>"
                    <?php echo $desde_favoritos ? 'data-from-favorites="1"' : ''; ?>
                    aria-label="<?php echo $esFav ? 'Quitar de favoritos' : 'Añadir a favoritos'; ?>"
                >
                    <?php echo $esFav ? '❤️' : '🤍'; ?>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($accionesSecundariasHtml)): ?>
            <div class="grid-admin-buttons">
                <?php echo $accionesSecundariasHtml; ?>
            </div>
        <?php endif; ?>
    </div>
</article>
