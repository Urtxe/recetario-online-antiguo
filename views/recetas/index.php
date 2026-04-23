<div class="content-card" id="recetas">
    <h2>Recetas</h2>
    
    <form id="formFiltros" action="index.php" method="GET" class="form-filtros">
        <input type="hidden" name="controller" value="receta">
        <input type="hidden" name="action" value="index">
        
    <select name="origen" id="filtro_origen">
    <option value="">-- Origen --</option>
    <?php if (isset($lista_origenes)): ?>
        <?php 
        $lista_origenes->data_seek(0); 
        while($o = $lista_origenes->fetch_assoc()): 
        ?>
            <option value="<?php echo htmlspecialchars($o['origen']); ?>" 
                <?php echo ($filtros['origen'] == $o['origen']) ? 'selected' : ''; ?>>
                <?php echo ucfirst(htmlspecialchars($o['origen'])); ?>
            </option>
        <?php endwhile; ?>
    <?php endif; ?>
</select>

<select name="tipo" id="filtro_tipo">
    <option value="">-- Tipo --</option>
    <?php if (isset($lista_tipos)): ?>
        <?php 
        $lista_tipos->data_seek(0);
        while($t = $lista_tipos->fetch_assoc()): 
        ?>
            <option value="<?php echo htmlspecialchars($t['tipo']); ?>" 
                <?php echo ($filtros['tipo'] == $t['tipo']) ? 'selected' : ''; ?>>
                <?php echo ucfirst(htmlspecialchars($t['tipo'])); ?>
            </option>
        <?php endwhile; ?>
    <?php endif; ?>
</select>

        <select name="duracion" id="filtro_duracion">
            <option value="">-- Duración --</option>
            <option value="0-30" <?php echo ($filtros['duracion'] == '0-30') ? 'selected' : ''; ?>>0-30 min</option>
            <option value="30-60" <?php echo ($filtros['duracion'] == '30-60') ? 'selected' : ''; ?>>30-60 min</option>
            <option value="60-120" <?php echo ($filtros['duracion'] == '60-120') ? 'selected' : ''; ?>>1-2 horas</option>
            <option value="120+" <?php echo ($filtros['duracion'] == '120+') ? 'selected' : ''; ?>>+2 horas</option>
        </select>
        
        <select name="dificultad" id="filtro_dificultad">
            <option value="">-- Dificultad --</option>
            <option value="facil" <?php echo ($filtros['dificultad'] == 'facil') ? 'selected' : ''; ?>>Fácil</option>
            <option value="medio" <?php echo ($filtros['dificultad'] == 'medio') ? 'selected' : ''; ?>>Media</option>
            <option value="dificil" <?php echo ($filtros['dificultad'] == 'dificil') ? 'selected' : ''; ?>>Difícil</option>
        </select>         

        <select name="usuario" id="filtro_usuario">
            <option value="">-- Creado por --</option>
            <?php 
            // Mostramos dinámicamente la lista de usuarios que tienen recetas
            if ($lista_usuarios) {
                $lista_usuarios->data_seek(0); 
                while($u = $lista_usuarios->fetch_assoc()): 
            ?>
                <option value="<?php echo $u['id']; ?>" <?php echo ($filtros['usuario'] == $u['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($u['nombre']); ?>
                </option>
            <?php 
                endwhile; 
            }
            ?>
        </select>

        <input type="text" name="buscar" id="input_buscar" placeholder="Buscar por nombre..." value="<?php echo htmlspecialchars($filtros['buscar']); ?>">

        <input type="text" name="ingredientes" id="input_ingredientes" placeholder="Buscar por ingrediente (ej: pollo, sal...)" 
                value="<?php echo htmlspecialchars($filtros['ingredientes'] ?? ''); ?>">
        <button type="submit" style="display:none;">Buscar</button>
    </form>
    
    <div id="contenedor-grid">
    <?php require 'views/recetas/grid.php'; ?>    

    <div class="paginacion">
        <?php 
        $params = $_GET;
        unset($params['pagina']); 
        $query_filtros = http_build_query($params);

        for($i=1; $i<=$total_paginas; $i++): ?>
            <a href="index.php?<?php echo $query_filtros; ?>&pagina=<?php echo $i; ?>" 
               class="<?php echo ($pagina == $i) ? 'active' : ''; ?>">
               <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<script>
// Búsqueda asíncrona
document.addEventListener("DOMContentLoaded", function() {
    const inputsTexto = document.querySelectorAll('.form-filtros input[type="text"]');
    const selects = document.querySelectorAll('.form-filtros select');
    const contenedorGrid = document.getElementById('contenedor-grid');
    let timeout = null;

    function realizarBusqueda(pagina) {
        const formulario = document.getElementById('formFiltros');
        const formData = new FormData(formulario);
        const params = new URLSearchParams(formData);
        params.set('ajax', '1');
        if (pagina) params.set('pagina', pagina);

        fetch('index.php?' + params.toString())
            .then(response => {
                if (!response.ok) throw new Error('Error en solicitud');
                return response.text();
            })
            .then(html => {
                contenedorGrid.innerHTML = html;
                bindPaginacion(); // re-enlazar los nuevos links de paginación
            })
            .catch(error => console.error('Error:', error));
    }

    // Enlazar los links de paginación para que usen AJAX
    function bindPaginacion() {
        contenedorGrid.querySelectorAll('.paginacion a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href, window.location.href);
                const p = url.searchParams.get('pagina');
                realizarBusqueda(p);
            });
        });
    }
    bindPaginacion();

    // Inputs de texto con debounce
    inputsTexto.forEach(input => {
        input.addEventListener('input', function() {
            const contenido = this.value.trim();
            clearTimeout(timeout);
            if (contenido.length === 0 || contenido.length >= 3) {
                timeout = setTimeout(() => realizarBusqueda(1), 300);
            }
        });
    });

    // Selectores con actualización inmediata
    selects.forEach(select => {
        select.addEventListener('change', function() {
            realizarBusqueda(1);
        });
    });
});
</script>