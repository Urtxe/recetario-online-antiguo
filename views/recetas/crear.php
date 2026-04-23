<div class="content-card">
    <h2>Crear Nueva Receta</h2>
    <form action="index.php?controller=receta&action=guardar" method="POST" enctype="multipart/form-data">
        
        <label>Nombre de la Receta (Título):</label>
        <input type="text" name="titulo" required>

        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <label>Duración (min):</label>
                <input type="number" name="duracion" placeholder="Ej: 45" required>
            </div>
            <div style="flex: 1;">
                <label>Dificultad:</label>
                <select name="dificultad" class="select-css" required>
                    <option value="facil">Fácil</option>
                    <option value="medio">Media</option>
                    <option value="dificil">Difícil</option>
                </select>
            </div>
        </div>

        <label>Origen de la Receta:</label>
        <select name="origen" class="select-css" required>
            <option value="euskalduna">Euskalduna</option>
            <option value="española">Española</option>
            <option value="italiana">Italiana</option>
            <option value="francesa">Francesa</option>
            <option value="europea">Europea</option>
            <option value="nordica">Nórdica</option>
            <option value="africa">África</option>
            <option value="asia">Asia</option>
            <option value="latina">Latina</option>
            <option value="caribeña">Caribeña</option>
            <option value="norteamericana">Norteamericana</option>
            <option value="fusión">Fusión</option>
        </select>
        
        <label>Tipo de Receta:</label>
        <select name="tipo" class="select-css" required>
            <option value="desayuno">Desayuno</option>
            <option value="aperitivo">Aperitivo</option>
            <option value="entrante">Entrante</option>
            <option value="principal">Principal</option>
            <option value="postre">Postre</option>
            <option value="merienda">Merienda</option>
            <option value="guarnición">Guarnición</option>
            <option value="salsa">Salsa</option>
        </select>

        <label>Foto de la Receta:</label>
        <input type="file" name="imagen" accept="image/*">

        <label style="margin-top: 20px; display:block;">Ingredientes:</label>
        <div id="ingredient-list">
            <div class="ingredient-item">
                <input type="text" name="ingrediente_nombre[]" placeholder="Nombre (Ej: Harina)" required>
                <input type="text" name="ingrediente_cantidad[]" placeholder="Cantidad (Ej: 200g)" required>
                <div style="width: 32px;"></div> 
            </div>
        </div>
        
        <button type="button" onclick="addIngredient()" class="btn-add-ingredient-style">Añadir Ingrediente</button>

        <label style="margin-top: 20px; display:block;">Elaboración / Preparación:</label>
        <textarea name="preparacion" rows="10" placeholder="Pasos de la receta..." required></textarea>

        <button type="submit" style="margin-top: 20px;">Guardar Receta Completa</button>
    </form>
</div>

<script>
// Añadir dinámicamente ingredientes
function addIngredient() {
    const container = document.getElementById('ingredient-list');
    const div = document.createElement('div');
    div.className = 'ingredient-item';
    div.innerHTML = `
        <input type="text" name="ingrediente_nombre[]" placeholder="Nombre" required>
        <input type="text" name="ingrediente_cantidad[]" placeholder="Cantidad" required>
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
    `;
    container.appendChild(div);
}
</script>