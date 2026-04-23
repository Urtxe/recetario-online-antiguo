<?php $isLocalDev = app_env('APP_ENV', 'local') === 'local'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Recetario Online</title>
    <link rel="stylesheet" href="public/css/main_auth.css">
</head>
<body>
<h1 class="titulo">Recetario Online</h1>

<div class="contenedor-auth">
  <?php if ($isLocalDev): ?>
    <div class="auth-banner">
      Modo local activo. La recuperación de contraseña muestra el token en pantalla y el 2FA queda listo para pruebas.
    </div>
  <?php endif; ?>
  
  <div class="botones">
    <button type="button" id="btn-login" class="tab-btn <?php echo isset($error_registro) ? '' : 'activo'; ?>" data-tab="login" aria-controls="panel-login" aria-selected="<?php echo isset($error_registro) ? 'false' : 'true'; ?>">Login</button>
    <button type="button" id="btn-register" class="tab-btn <?php echo isset($error_registro) ? 'activo' : ''; ?>" data-tab="register" aria-controls="panel-registro" aria-selected="<?php echo isset($error_registro) ? 'true' : 'false'; ?>">Registrarte</button>
  </div>

  <div class="paneles">
    <!-- Panel Login -->
    <div id="panel-login" class="panel <?php echo isset($error_registro) ? '' : 'activo'; ?>" data-panel="login">
      <h3>Iniciar Sesión</h3>
      <?php if (isset($error_login)): ?>
        <p class="error-msg" style="color: red; text-align:center;"><?php echo htmlspecialchars($error_login, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>

      <form action="index.php?controller=auth&action=login" method="post">
        <?php echo csrf_field(); ?>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com">
        
        <label for="login-pass">Contraseña:</label>
        <input type="password" id="login-pass" name="password" required>
        
        <button type="submit">Entrar</button>
      </form>

      <div style="margin-top: 15px; text-align: center;">
        <a href="#panel-recuperacion" style="font-size: 0.9rem;" data-scroll-panel="recover">¿Has olvidado tu contraseña?</a>
      </div>
    </div>

    <!-- Panel Registro -->
    <div id="panel-registro" class="panel <?php echo isset($error_registro) ? 'activo' : ''; ?>" data-panel="register">
      <h3>Crear Cuenta</h3>

      <?php if (isset($error_registro)): ?>
        <p class="error-msg" style="color: red; text-align:center;"><?php echo htmlspecialchars($error_registro, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>

      <form action="index.php?controller=auth&action=registro" method="post">
        <?php echo csrf_field(); ?>
        <label for="reg-usuario">Usuario:</label>
        <input type="text" id="reg-usuario" name="usuario" required>
        
        <label for="reg-email">Email:</label>
        <input type="email" id="reg-email" name="email" required>
        
        <label for="reg-pass">Contraseña:</label>
        <input type="password" id="reg-pass" name="password" required>
        
        <label for="reg-pass-conf">Confirmar Contraseña:</label>
        <input type="password" id="reg-pass-conf" name="password-confirm" required>
        
        <button type="submit">Registrarme</button>
      </form>
    </div>

    <?php if (isset($_SESSION['pending_2fa_user'])): ?>
      <div id="panel-2fa" class="panel activo" style="margin-top: 20px;">
        <h3>Verificación en dos pasos</h3>
        <?php if (isset($error_2fa)): ?>
          <p class="error-msg" style="color: red; text-align:center;"><?php echo htmlspecialchars($error_2fa, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form action="index.php?controller=auth&action=verificar2fa" method="post">
          <?php echo csrf_field(); ?>
          <label for="codigo_2fa">Código de verificación</label>
          <input type="text" id="codigo_2fa" name="codigo_2fa" required inputmode="numeric" autocomplete="one-time-code">
          <button type="submit">Verificar</button>
        </form>
      </div>
    <?php endif; ?>

    <div id="panel-recuperacion" class="panel activo" style="margin-top: 20px;">
      <h3>Recuperar contraseña</h3>
      <?php if ($isLocalDev): ?>
        <p class="auth-note">Aquí puedes probar el flujo sin correo real. El token te aparecerá en el mensaje flash después de solicitarlo.</p>
      <?php endif; ?>
      <form action="index.php?controller=auth&action=solicitarRecuperacion" method="post">
        <?php echo csrf_field(); ?>
        <label for="reset-email">Email</label>
        <input type="email" id="reset-email" name="email" required>
        <button type="submit">Solicitar enlace</button>
      </form>

      <form action="index.php?controller=auth&action=resetearPassword" method="post" style="margin-top: 15px;">
        <?php echo csrf_field(); ?>
        <label for="reset-token">Token de recuperación</label>
        <input type="text" id="reset-token" name="reset_token" required>

        <label for="reset-pass">Nueva contraseña</label>
        <input type="password" id="reset-pass" name="password" required>

        <label for="reset-pass-confirm">Confirmar contraseña</label>
        <input type="password" id="reset-pass-confirm" name="password_confirm" required>

        <button type="submit">Cambiar contraseña</button>
      </form>
    </div>
    
  </div>
</div>

<footer class="footer">
    © 2026 Todos los derechos reservados · Developed By <strong>Mikel Urtxegi</strong>
</footer>

<script src="public/js/auth.js"></script>

</body>
</html>
