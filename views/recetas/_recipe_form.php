<?php
$esEdicion = !empty($receta['id']);
$tituloFormulario = $esEdicion ? 'Modificar Receta' : 'Crear Nueva Receta';
$accionFormulario = $esEdicion
    ? 'index.php?controller=receta&action=actualizar'
    : 'index.php?controller=receta&action=guardar';
$textoBoton = $esEdicion ? 'Guardar Cambios' : 'Guardar Receta Completa';

$etiquetasDificultad = [
    'facil' => 'Fácil',
    'medio' => 'Media',
    'dificil' => 'Difícil'
];

$etiquetasOrigen = [
    'euskalduna' => 'Euskalduna',
    'española' => 'Española',
    'italiana' => 'Italiana',
    'francesa' => 'Francesa',
    'europea' => 'Europea',
    'nordica' => 'Nórdica',
    'africa' => 'África',
    'asia' => 'Asia',
    'latina' => 'Latina',
    'caribeña' => 'Caribeña',
    'norteamericana' => 'Norteamericana',
    'fusión' => 'Fusión',
    'europa del este' => 'Europa del Este',
    'escandinava' => 'Escandinava'
];

$etiquetasTipo = [
    'desayuno' => 'Desayuno',
    'aperitivo' => 'Aperitivo',
    'entrante' => 'Entrante',
    'principal' => 'Principal',
    'postre' => 'Postre',
    'merienda' => 'Merienda',
    'guarnición' => 'Guarnición',
    'salsa' => 'Salsa'
];
?>

<div class="content-card">
    <h2><?php echo $tituloFormulario; ?></h2>

    <form
        action="<?php echo $accionFormulario; ?>"
        method="POST"
        enctype="multipart/form-data"
        class="recipe-form"
        data-recipe-form
    >
        <?php echo csrf_field(); ?>

        <?php if ($esEdicion): ?>
            <input type="hidden" name="id_receta" value="<?php echo (int)$receta['id']; ?>">
        <?php endif; ?>

        <label for="titulo">Nombre de la receta</label>
        <input
            type="text"
            id="titulo"
            name="titulo"
            value="<?php echo htmlspecialchars($receta['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            required
        >

        <div class="form-row form-row-split">
            <div class="form-field">
                <label for="duracion">Duración (min)</label>
                <input
                    type="number"
                    id="duracion"
                    name="duracion"
                    min="1"
                    value="<?php echo htmlspecialchars((string)($receta['duracion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="Ej: 45"
                    required
                >
            </div>

            <div class="form-field">
                <label for="dificultad">Dificultad</label>
                <select id="dificultad" name="dificultad" class="select-css" required>
                    <?php foreach ($opciones_dificultad as $opcion): ?>
                        <option value="<?php echo htmlspecialchars($opcion, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($receta['dificultad'] ?? '') === $opcion ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($etiquetasDificultad[$opcion] ?? ucfirst($opcion), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row form-row-split">
            <div class="form-field">
                <label for="origen">Origen</label>
                <select id="origen" name="origen" class="select-css" required>
                    <option value="">Selecciona origen</option>
                    <?php foreach ($opciones_origen as $opcion): ?>
                        <option value="<?php echo htmlspecialchars($opcion, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($receta['origen'] ?? '') === $opcion ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($etiquetasOrigen[$opcion] ?? ucfirst($opcion), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="tipo">Tipo de receta</label>
                <select id="tipo" name="tipo" class="select-css" required>
                    <option value="">Selecciona tipo</option>
                    <?php foreach ($opciones_tipo as $opcion): ?>
                        <option value="<?php echo htmlspecialchars($opcion, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($receta['tipo'] ?? '') === $opcion ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($etiquetasTipo[$opcion] ?? ucfirst($opcion), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($esEdicion && !empty($receta['imagen_url'])): ?>
            <div class="form-section-heading">
                <span>Foto actual</span>
            </div>
            <img
                src="<?php echo htmlspecialchars(recipe_image_url($receta['imagen_url']), ENT_QUOTES, 'UTF-8'); ?>"
                class="recipe-form-image-preview"
                alt="Foto actual de <?php echo htmlspecialchars($receta['titulo'] ?? 'la receta', ENT_QUOTES, 'UTF-8'); ?>"
            >
        <?php endif; ?>

        <label for="imagen"><?php echo $esEdicion ? 'Cambiar foto (opcional)' : 'Foto de la receta'; ?></label>
        <input type="file" id="imagen" name="imagen" accept="image/*">

        <div class="form-section-heading">
            <span>Ingredientes</span>
        </div>
        <div id="ingredient-list" class="ingredient-list-editor" data-ingredient-list>
            <?php foreach ($ingredientes as $ing): ?>
                <div class="ingredient-item">
                    <input
                        type="text"
                        name="ingrediente_nombre[]"
                        value="<?php echo htmlspecialchars($ing['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Nombre (Ej: Harina)"
                        required
                    >
                    <input
                        type="text"
                        name="ingrediente_cantidad[]"
                        value="<?php echo htmlspecialchars($ing['cantidad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Cantidad (Ej: 200 g)"
                        required
                    >
                    <button
                        type="button"
                        class="btn-remove"
                        data-remove-ingredient
                        aria-label="Eliminar ingrediente"
                    >
                        ×
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="btn-add-ingredient-style" data-add-ingredient>
            Añadir ingrediente
        </button>

        <label for="preparacion">Elaboración / Preparación</label>
        <textarea id="preparacion" name="preparacion" rows="10" placeholder="Pasos de la receta..." required><?php echo htmlspecialchars($receta['preparacion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

        <button type="submit"><?php echo $textoBoton; ?></button>
    </form>
</div>

<template id="ingredient-template">
    <div class="ingredient-item">
        <input type="text" name="ingrediente_nombre[]" placeholder="Nombre (Ej: Harina)" required>
        <input type="text" name="ingrediente_cantidad[]" placeholder="Cantidad (Ej: 200 g)" required>
        <button type="button" class="btn-remove" data-remove-ingredient aria-label="Eliminar ingrediente">×</button>
    </div>
</template>

<script src="public/js/recipe-form.js"></script>
