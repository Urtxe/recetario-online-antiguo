<?php 
// Verificar favoritos
$uModel = new Usuario(); 
?>

<div class="recetas-grid-container">
    <?php if ($recetas && $recetas->num_rows > 0): ?>
        <?php while($receta = $recetas->fetch_assoc()): ?>
            <div class="receta-item">
                <?php $esFav = $uModel->esFavorito($_SESSION['usuario_id'], $receta['id']); ?>
                <div class="btn-fav-container">
                    <button class="btn-fav" data-id="<?php echo $receta['id']; ?>" <?php echo (isset($desde_favoritos) && $desde_favoritos) ? 'data-from-favorites="1"' : ''; ?>>
                        <?php echo $esFav ? '❤️' : '🤍'; ?>
                    </button>
                </div>

                <a href="index.php?controller=receta&action=ver&id=<?php echo $receta['id']; ?>" class="link-estirado" alt="<?php echo htmlspecialchars($receta['titulo']); ?>">
                    <?php if (!empty($receta['imagen_url'])): ?>
                        <img src="public/uploads/<?php echo htmlspecialchars($receta['imagen_url']); ?>" class="receta-miniatura">
                    <?php else: ?>
                        <div class="sin-imagen-placeholder"><span>Imagen no subida</span></div>                    
                    <?php endif; ?>
                    <div style="padding: 15px; flex-grow: 1;">
                        <h3 class="receta-titulo"><?php echo htmlspecialchars($receta['titulo']); ?></h3>
                        <p style="font-size: 0.85rem; color: #666; margin: 8px 0;">
                            <strong>🌍 Origen:</strong> <?php echo htmlspecialchars($receta['origen']); ?><br>
                            <strong>🍽️ Tipo:</strong> <?php echo htmlspecialchars($receta['tipo']); ?><br>
                            <strong>⏱️ Tiempo:</strong> <?php echo htmlspecialchars($receta['duracion']); ?> min
                        </p>
                        <p style="font-size: 0.85rem; color: #E67E22; font-weight: bold;">
                            Por: <?php echo htmlspecialchars($receta['nombre_autor']); ?>
                        </p>
                    </div>
                </a>

                <?php if ($_SESSION['usuario_id'] == $receta['usuario_id']): ?>
                    <div class="grid-admin-buttons">
                        <a href="index.php?controller=receta&action=editar&id=<?php echo $receta['id']; ?>" class="btn-edit-mini">Editar</a>
                        <form action="index.php?controller=receta&action=borrar&id=<?php echo $receta['id']; ?>" method="post" style="display:inline;" onsubmit="return confirm('¿Seguro?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $receta['id']; ?>">
                            <button type="submit" class="btn-delete-mini">Borrar</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #777;">
            No se encontraron recetas
        </p>
    <?php endif; ?>
</div>
