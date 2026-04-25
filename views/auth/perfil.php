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
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" required autocomplete="name">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required autocomplete="email">

        <h3 style="margin-top: 25px;">Cambiar contraseña</h3>

        <label for="password_actual">Contraseña actual</label>
        <input type="password" id="password_actual" name="password_actual" autocomplete="current-password">

        <label for="password_nueva">Nueva contraseña</label>
        <input type="password" id="password_nueva" name="password_nueva" autocomplete="new-password">

        <label for="password_confirmar">Confirmar nueva contraseña</label>
        <input type="password" id="password_confirmar" name="password_confirmar" autocomplete="new-password">

        <button type="submit" style="margin-top: 20px;">Guardar cambios</button>
    </form>

    <?php if (is_admin_user($usuario) && isset($usuario['two_factor_enabled'])): ?>
        <div style="margin-top: 30px; padding: 15px; background: #f8f8f8; border-radius: 8px;">
            <h3>Seguridad extra para admin</h3>
            <p><strong>2FA:</strong> <?php echo !empty($usuario['two_factor_enabled']) ? 'Activado' : 'Desactivado'; ?></p>

            <?php if (!empty($twoFactorSetupSecret)): ?>
                <p><strong>Secreto de configuración:</strong> <code><?php echo htmlspecialchars($twoFactorSetupSecret); ?></code></p>
                <p>Guárdalo ahora en tu aplicación de autenticación. Este secreto solo se muestra una vez.</p>
            <?php endif; ?>

            <form action="index.php?controller=auth&action=activar2fa" method="POST" style="margin-top: 15px;">
                <?php echo csrf_field(); ?>
                <label for="codigo_2fa_setup">Código de verificación</label>
                <input type="text" id="codigo_2fa_setup" name="codigo_2fa" placeholder="123456">
                <button type="submit">Activar / Verificar 2FA</button>
            </form>

            <form action="index.php?controller=auth&action=activar2fa" method="POST" style="margin-top: 10px;">
                <?php echo csrf_field(); ?>
                <button type="submit">Generar nuevo secreto 2FA</button>
            </form>

            <form action="index.php?controller=auth&action=desactivar2fa" method="POST" style="margin-top: 10px;">
                <?php echo csrf_field(); ?>
                <label for="password_actual_2fa">Contraseña actual</label>
                <input type="password" id="password_actual_2fa" name="password_actual" autocomplete="current-password" required>
                <button type="submit">Desactivar 2FA</button>
            </form>
        </div>
    <?php endif; ?>
</div>
