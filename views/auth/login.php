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
  
  <div class="botones">
    <button id="btn-login" class="tab-btn <?php echo isset($error_registro) ? '' : 'activo'; ?>">Login</button>
    <button id="btn-register" class="tab-btn <?php echo isset($error_registro) ? 'activo' : ''; ?>">Registrarte</button>
  </div>

  <div class="paneles">
    <!-- Panel Login -->
    <div id="panel-login" class="panel <?php echo isset($error_registro) ? '' : 'activo'; ?>">
      <h3>Iniciar Sesión</h3>
      <?php if (isset($error_login)): ?>
        <p class="error-msg" style="color: red; text-align:center;"><?php echo $error_login; ?></p>
      <?php endif; ?>

      <form action="index.php?controller=auth&action=login" method="post">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com">
        
        <label for="login-pass">Contraseña:</label>
        <input type="password" id="login-pass" name="password" required>
        
        <button type="submit">Entrar</button>
      </form>
    </div>

    <!-- Panel Registro -->
    <div id="panel-registro" class="panel <?php echo isset($error_registro) ? 'activo' : ''; ?>">
      <h3>Crear Cuenta</h3>

      <?php if (isset($error_registro)): ?>
        <p class="error-msg" style="color: red; text-align:center;"><?php echo $error_registro; ?></p>
      <?php endif; ?>

      <form action="index.php?controller=auth&action=registro" method="post">
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
    
  </div>
</div>

<footer class="footer">
    © 2026 Todos los derechos reservados · Developed By <strong>Mikel Urtxegi</strong>
</footer>

<script src="public/js/auth.js"></script>

</body>
</html>