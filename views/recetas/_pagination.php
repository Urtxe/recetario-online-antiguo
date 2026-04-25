<?php if (($total_paginas ?? 0) > 1): ?>
    <div class="paginacion">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="<?php echo htmlspecialchars($pagination_base_url . '&pagina=' . $i, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo ($pagina == $i) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
