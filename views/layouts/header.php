<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetario Online</title>
    <?php echo csrf_meta_tag(); ?>
    <link rel="stylesheet" href="public/css/main.css">
</head>
<body>
    <header class="main-header">
        <div class="user-info">
            ¡Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Invitado'); ?></strong>!<br>
            <span class="user-info-links">
                <a href="index.php?controller=auth&action=perfil">Mi perfil</a> |
                <?php if (is_admin_user($_SESSION ?? [])): ?>
                    <a href="index.php?controller=admin&action=dashboard">Admin</a> |
                <?php endif; ?>
                <form action="index.php?controller=auth&action=logout" method="post" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="button-link">Cerrar Sesión</button>
                </form>
            </span>
        </div>
        <nav class="main-nav">
            <h1>RECETARIO ONLINE</h1>
            <ul>
                <li><a href="index.php?controller=receta&action=index">Inicio</a></li>
                <li><a href="index.php?controller=receta&action=misFavoritos">Mis Favoritos</a></li>
                <li><a href="index.php?controller=receta&action=crear">Crear Receta</a></li>
            </ul>
        </nav>
    </header>

<?php if (isset($_SESSION['mensaje_flash'])): ?>
    <div class="alerta alerta-<?php echo $_SESSION['tipo_flash']; ?>" id="flash-message">
        <?php echo htmlspecialchars($_SESSION['mensaje_flash'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php unset($_SESSION['mensaje_flash'], $_SESSION['tipo_flash']); ?>
<?php endif; ?>

    <main>
