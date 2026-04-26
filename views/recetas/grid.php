<div class="recetas-grid-container">
    <?php if ($recetas && $recetas->num_rows > 0): ?>
        <?php while($receta = $recetas->fetch_assoc()): ?>
            <?php
                $mostrarFavorito = true;
                $accionesSecundariasHtml = '';

                if ((int)($_SESSION['usuario_id'] ?? 0) === (int)($receta['usuario_id'] ?? 0)) {
                    ob_start();
                    ?>
                    <a href="index.php?controller=receta&action=editar&id=<?php echo (int)$receta['id']; ?>" class="btn-edit-mini">Editar</a>
                    <form action="index.php?controller=receta&action=borrar&id=<?php echo (int)$receta['id']; ?>" method="post" class="inline-form" data-confirm="¿Seguro?">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$receta['id']; ?>">
                        <button type="submit" class="btn-delete-mini">Borrar</button>
                    </form>
                    <?php
                    $accionesSecundariasHtml = ob_get_clean();
                }

                include __DIR__ . '/_recipe_card.php';
            ?>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="empty-state">No se encontraron recetas</p>
    <?php endif; ?>
</div>
