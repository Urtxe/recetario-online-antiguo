<div class="content-card">
    <h2>Mi perfil</h2>

    <div style="margin-bottom: 20px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
        <p><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email'] ?? ''); ?></p>
        <p><strong>Mis recetas:</strong> <?php echo (int)$misRecetas; ?></p>
        <p><strong>Mis favoritos:</strong> <?php echo (int)$misFavoritos; ?></p>
    </div>

    <form action="index.php?controller=auth&action=actualizarPerfil" method="POST">
        <?php echo csrf_field(); ?>

        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>

        <h3 style="margin-top: 25px;">Cambiar contraseña</h3>

        <label for="password_actual">Contraseña actual</label>
        <input type="password" id="password_actual" name="password_actual">

        <label for="password_nueva">Nueva contraseña</label>
        <input type="password" id="password_nueva" name="password_nueva">

        <label for="password_confirmar">Confirmar nueva contraseña</label>
        <input type="password" id="password_confirmar" name="password_confirmar">

        <button type="submit" style="margin-top: 20px;">Guardar cambios</button>
    </form>
</div>
