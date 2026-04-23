<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetario Online</title>
    <link rel="stylesheet" href="public/css/main.css">
</head>
<body>
    <header class="main-header">
        <div class="user-info">
            ¡Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Invitado'); ?></strong>!<br>
            <a href="index.php?controller=auth&action=logout">Cerrar Sesión</a>
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
        <?php echo $_SESSION['mensaje_flash']; ?>
    </div>
    <?php unset($_SESSION['mensaje_flash'], $_SESSION['tipo_flash']); ?>
    <script>
        // Desvanecer mensaje tras 3 segundos
        setTimeout(() => {
            const msg = document.getElementById('flash-message');
            if(msg) msg.style.display = 'none';
        }, 3000);
    </script>
    <?php endif; ?>

    <main>