<form id="formFiltros" action="index.php" method="GET" class="form-filtros">
    <input type="hidden" name="controller" value="receta">
    <input type="hidden" name="action" value="index">

    <select name="origen" id="filtro_origen">
        <option value="">-- Origen --</option>
        <?php if (isset($lista_origenes)): ?>
            <?php $lista_origenes->data_seek(0); ?>
            <?php while ($o = $lista_origenes->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($o['origen']); ?>" <?php echo ($filtros['origen'] == $o['origen']) ? 'selected' : ''; ?>>
                    <?php echo ucfirst(htmlspecialchars($o['origen'])); ?>
                </option>
            <?php endwhile; ?>
        <?php endif; ?>
    </select>

    <select name="tipo" id="filtro_tipo">
        <option value="">-- Tipo --</option>
        <?php if (isset($lista_tipos)): ?>
            <?php $lista_tipos->data_seek(0); ?>
            <?php while ($t = $lista_tipos->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($t['tipo']); ?>" <?php echo ($filtros['tipo'] == $t['tipo']) ? 'selected' : ''; ?>>
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
        <?php if ($lista_usuarios): ?>
            <?php $lista_usuarios->data_seek(0); ?>
            <?php while ($u = $lista_usuarios->fetch_assoc()): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo ($filtros['usuario'] == $u['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($u['nombre']); ?>
                </option>
            <?php endwhile; ?>
        <?php endif; ?>
    </select>

    <select name="orden" id="filtro_orden">
        <?php foreach ($ordenes_disponibles as $ordenClave => $ordenLabel): ?>
            <option value="<?php echo htmlspecialchars($ordenClave); ?>" <?php echo (($filtros['orden'] ?? 'fecha') === $ordenClave) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($ordenLabel); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="text" name="buscar" id="input_buscar" placeholder="Busca por receta, preparación o autor..." value="<?php echo htmlspecialchars($filtros['buscar']); ?>">
    <small class="filter-help">También encuentra coincidencias en ingredientes si el texto está en la receta.</small>

    <input type="text" name="ingredientes" id="input_ingredientes" placeholder="Buscar por ingrediente (ej: pollo, sal...)" value="<?php echo htmlspecialchars($filtros['ingredientes'] ?? ''); ?>">
    <button type="submit" style="display:none;">Buscar</button>
</form>
