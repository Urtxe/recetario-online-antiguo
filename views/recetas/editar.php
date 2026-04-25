<div class="content-card">
    <h2>Modificar Receta</h2>
    <form action="index.php?controller=receta&action=actualizar" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        
        <input type="hidden" name="id_receta" value="<?php echo $receta['id']; ?>">
        
        <label>Nombre de la Receta:</label>
        <input type="text" name="titulo" value="<?php echo htmlspecialchars($receta['titulo']); ?>" required>

        <label>Duración (min):</label>
        <input type="number" name="duracion" value="<?php echo htmlspecialchars($receta['duracion']); ?>" required>

        <label>Dificultad:</label>
        <select name="dificultad" class="select-css" required>
            <option value="facil" <?php if($receta['dificultad']=='facil') echo 'selected'; ?>>Fácil</option>
            <option value="medio" <?php if($receta['dificultad']=='medio') echo 'selected'; ?>>Media</option>
            <option value="dificil" <?php if($receta['dificultad']=='dificil') echo 'selected'; ?>>Difícil</option>
        </select>
        
        <label>Origen:</label>
        <select name="origen" class="select-css" required>
             <option value="" <?php if(empty($receta['origen'])) echo 'selected'; ?>>-- Selecciona origen --</option>
             <option value="euskalduna" <?php if($receta['origen']=='euskalduna') echo 'selected'; ?>>Euskalduna</option>
             <option value="española" <?php if($receta['origen']=='española') echo 'selected'; ?>>Española</option>
             <option value="italiana" <?php if($receta['origen']=='italiana') echo 'selected'; ?>>Italiana</option>
             <option value="francesa" <?php if($receta['origen']=='francesa') echo 'selected'; ?>>Francesa</option>
             <option value="europa del este" <?php if($receta['origen']=='europa del este') echo 'selected'; ?>>Europa del Este</option>
             <option value="escandinava" <?php if($receta['origen']=='escandinava') echo 'selected'; ?>>Escandinava</option>
             <option value="africa" <?php if($receta['origen']=='africa') echo 'selected'; ?>>África</option>
             <option value="asia" <?php if($receta['origen']=='asia') echo 'selected'; ?>>Asia</option>
             <option value="latina" <?php if($receta['origen']=='latina') echo 'selected'; ?>>Latina</option>
             <option value="caribeña" <?php if($receta['origen']=='caribeña') echo 'selected'; ?>>Caribeña</option>
             <option value="norteamericana" <?php if($receta['origen']=='norteamericana') echo 'selected'; ?>>Norteamericana</option>
             <option value="fusión" <?php if($receta['origen']=='fusión') echo 'selected'; ?>>Fusión</option>
        </select>
        
        <label>Tipo:</label>
        <select name="tipo" class="select-css" required>
            <option value="" <?php if(empty($receta['tipo'])) echo 'selected'; ?>>-- Selecciona tipo --</option>
            <option value="desayuno" <?php if($receta['tipo']=='desayuno') echo 'selected'; ?>>Desayuno</option>
            <option value="aperitivo" <?php if($receta['tipo']=='aperitivo') echo 'selected'; ?>>Aperitivo</option>
            <option value="entrante" <?php if($receta['tipo']=='entrante') echo 'selected'; ?>>Entrante</option>
            <option value="principal" <?php if($receta['tipo']=='principal') echo 'selected'; ?>>Principal</option>
            <option value="postre" <?php if($receta['tipo']=='postre') echo 'selected'; ?>>Postre</option>
            <option value="merienda" <?php if($receta['tipo']=='merienda') echo 'selected'; ?>>Merienda</option>
            <option value="guarnición" <?php if($receta['tipo']=='guarnición') echo 'selected'; ?>>Guarnición</option>
            <option value="salsa" <?php if($receta['tipo']=='salsa') echo 'selected'; ?>>Salsa</option>
        </select>

        <label>Foto actual:</label>
        <?php if (!empty($receta['imagen_url'])): ?>
            <img src="<?php echo htmlspecialchars(recipe_image_url($receta['imagen_url']), ENT_QUOTES, 'UTF-8'); ?>" style="width:100px; display:block; margin-bottom:10px; border-radius:4px;">
        <?php endif; ?>
        
        <label>Cambiar Foto (opcional):</label>
        <input type="file" name="imagen" accept="image/*">

        <label style="margin-top: 20px; display:block;">Ingredientes:</label>
        <div id="ingredient-list">
            <?php foreach ($ingredientes as $ing): ?>
            <div class="ingredient-item">
                <input type="text" name="ingrediente_nombre[]" value="<?php echo htmlspecialchars($ing['nombre']); ?>" required>
                <input type="text" name="ingrediente_cantidad[]" value="<?php echo htmlspecialchars($ing['cantidad']); ?>" required>
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" onclick="addIngredient()" class="btn-add-ingredient-style">Añadir Ingrediente</button>

        <label style="margin-top: 20px; display:block;">Preparación:</label>
        <textarea name="preparacion" rows="10" required><?php echo htmlspecialchars($receta['preparacion']); ?></textarea>

        <button type="submit" style="margin-top: 20px;">Guardar Cambios</button>
    </form>
</div>

<script>
// Añadir dinámicamente ingredientes
function addIngredient() {
    const div = document.createElement('div');
    div.className = 'ingredient-item';
    div.innerHTML = `
        <input type="text" name="ingrediente_nombre[]" placeholder="Nombre" required>
        <input type="text" name="ingrediente_cantidad[]" placeholder="Cantidad" required>
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
    `;
    document.getElementById('ingredient-list').appendChild(div);
}
</script>
