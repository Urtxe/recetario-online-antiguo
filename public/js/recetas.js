document.addEventListener("DOMContentLoaded", function() {
    const formFiltros = document.getElementById('formFiltros');
    const contenedorGrid = document.getElementById('contenedor-grid');

    if (!formFiltros || !contenedorGrid) {
        return;
    }

    const apiBase = 'index.php';
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    const inputsTexto = formFiltros.querySelectorAll('input[type="text"]');
    const selects = formFiltros.querySelectorAll('select');
    let timeout = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildPagination(paginaActual, totalPaginas, params) {
        if (totalPaginas <= 1) {
            return '';
        }

        let html = '<div class="paginacion">';
        for (let i = 1; i <= totalPaginas; i++) {
            const viewParams = new URLSearchParams(params.toString());
            viewParams.set('controller', 'receta');
            viewParams.set('action', 'index');
            viewParams.delete('ajax');
            viewParams.set('pagina', i);
            const active = i === paginaActual ? ' active' : '';
            html += `<a href="index.php?${viewParams.toString()}" class="${active.trim()}">${i}</a>`;
        }
        html += '</div>';
        return html;
    }

    function buildRecipeCard(receta, usuarioIdActual) {
        const tieneImagen = receta.imagen_src && receta.imagen_src.length > 0;
        const esAutor = usuarioIdActual === receta.usuario_id;
        const favIcon = receta.es_favorito ? '❤️' : '🤍';
        const imageHtml = tieneImagen
            ? `<img src="${escapeHtml(receta.imagen_src)}" class="receta-miniatura" alt="${escapeHtml(receta.titulo)}">`
            : '<div class="sin-imagen-placeholder"><span>Imagen no subida</span></div>';
        const popularityHtml = Number(receta.favorite_count || 0) > 0
            ? `<span class="recipe-chip">Favoritos: ${escapeHtml(receta.favorite_count)}</span>`
            : '';

        return `
            <div class="receta-item">
                <div class="btn-fav-container">
                    <button class="btn-fav" data-id="${receta.id}">${favIcon}</button>
                </div>
                <a href="index.php?controller=receta&action=ver&id=${receta.id}" class="link-estirado" aria-label="${escapeHtml(receta.titulo)}">
                    ${imageHtml}
                    <div class="recipe-card-body">
                        <h3 class="receta-titulo">${escapeHtml(receta.titulo)}</h3>
                        <p class="recipe-card-meta">
                            <strong>Origen:</strong> ${escapeHtml(receta.origen)}<br>
                            <strong>Tipo:</strong> ${escapeHtml(receta.tipo)}<br>
                            <strong>Tiempo:</strong> ${escapeHtml(receta.duracion)} min
                        </p>
                        <div class="recipe-card-footer">
                            <p class="recipe-author-label">Por: ${escapeHtml(receta.nombre_autor)}</p>
                            ${popularityHtml}
                        </div>
                    </div>
                </a>
                ${esAutor ? `
                    <div class="grid-admin-buttons">
                        <a href="index.php?controller=receta&action=editar&id=${receta.id}" class="btn-edit-mini">Editar</a>
                        <form action="index.php?controller=receta&action=borrar&id=${receta.id}" method="post" style="display:inline;" onsubmit="return confirm('¿Seguro?')">
                            <input type="hidden" name="_csrf_token" value="${escapeHtml(csrfToken)}">
                            <input type="hidden" name="id" value="${receta.id}">
                            <button type="submit" class="btn-delete-mini">Borrar</button>
                        </form>
                    </div>
                ` : ''}
            </div>
        `;
    }

    function buildGrid(recetas, usuarioIdActual) {
        if (!recetas.length) {
            return '<p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #777;">No se encontraron recetas</p>';
        }

        return `
            <div class="recetas-grid-container">
                ${recetas.map(receta => buildRecipeCard(receta, usuarioIdActual)).join('')}
            </div>
        `;
    }

    function realizarBusqueda(pagina) {
        const formData = new FormData(formFiltros);
        const params = new URLSearchParams(formData);
        params.set('controller', 'api');
        params.set('action', 'recetas');
        params.set('pagina', pagina || 1);

        fetch(apiBase + '?' + params.toString())
            .then(response => {
                if (!response.ok) throw new Error('Error en solicitud');
                return response.json();
            })
            .then(payload => {
                if (!payload.success) {
                    throw new Error(payload.message || 'No se pudo cargar el listado');
                }

                contenedorGrid.innerHTML = buildGrid(payload.data.recetas, payload.data.usuario_id_actual);
                contenedorGrid.innerHTML += buildPagination(payload.data.pagina, payload.data.total_paginas, params);
                bindPaginacion();
                const currentUrlParams = new URLSearchParams(new FormData(formFiltros));
                currentUrlParams.set('controller', 'receta');
                currentUrlParams.set('action', 'index');
                currentUrlParams.set('pagina', payload.data.pagina);
                window.history.replaceState(null, '', `index.php?${currentUrlParams.toString()}`);
            })
            .catch(error => console.error('Error:', error));
    }

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

    inputsTexto.forEach(input => {
        input.addEventListener('input', function() {
            const contenido = this.value.trim();
            clearTimeout(timeout);
            if (contenido.length === 0 || contenido.length >= 3) {
                timeout = setTimeout(() => realizarBusqueda(1), 300);
            }
        });
    });

    selects.forEach(select => {
        select.addEventListener('change', function() {
            realizarBusqueda(1);
        });
    });
});
