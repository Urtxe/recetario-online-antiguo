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
            const url = new URL(window.location.href);
            url.search = params.toString();
            url.searchParams.set('pagina', i);
            const active = i === paginaActual ? ' active' : '';
            html += `<a href="${url.toString()}" class="${active.trim()}">${i}</a>`;
        }
        html += '</div>';
        return html;
    }

    function buildGrid(recetas, usuarioIdActual) {
        if (!recetas.length) {
            return '<p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #777;">No se encontraron recetas</p>';
        }

        return `
            <div class="recetas-grid-container">
                ${recetas.map(receta => {
                    const tieneImagen = receta.imagen_url && receta.imagen_url.length > 0;
                    const esAutor = usuarioIdActual === receta.usuario_id;
                    const favIcon = receta.es_favorito ? '❤️' : '🤍';
                    const imageHtml = tieneImagen
                        ? `<img src="public/uploads/${escapeHtml(receta.imagen_url)}" class="receta-miniatura">`
                        : '<div class="sin-imagen-placeholder"><span>Imagen no subida</span></div>';
                    const deleteForm = esAutor ? `
                        <form action="index.php?controller=receta&action=borrar&id=${receta.id}" method="post" style="display:inline;" onsubmit="return confirm('¿Seguro?')">
                            <input type="hidden" name="_csrf_token" value="${escapeHtml(csrfToken)}">
                            <input type="hidden" name="id" value="${receta.id}">
                            <button type="submit" class="btn-delete-mini">Borrar</button>
                        </form>
                    ` : '';

                    return `
                        <div class="receta-item">
                            <div class="btn-fav-container">
                                <button class="btn-fav" data-id="${receta.id}">${favIcon}</button>
                            </div>
                            <a href="index.php?controller=receta&action=ver&id=${receta.id}" class="link-estirado" alt="${escapeHtml(receta.titulo)}">
                                ${imageHtml}
                                <div style="padding: 15px; flex-grow: 1;">
                                    <h3 class="receta-titulo">${escapeHtml(receta.titulo)}</h3>
                                    <p style="font-size: 0.85rem; color: #666; margin: 8px 0;">
                                        <strong>🌍 Origen:</strong> ${escapeHtml(receta.origen)}<br>
                                        <strong>🍽️ Tipo:</strong> ${escapeHtml(receta.tipo)}<br>
                                        <strong>⏱️ Tiempo:</strong> ${escapeHtml(receta.duracion)} min
                                    </p>
                                    <p style="font-size: 0.85rem; color: #E67E22; font-weight: bold;">
                                        Por: ${escapeHtml(receta.nombre_autor)}
                                    </p>
                                </div>
                            </a>
                            ${esAutor ? `
                                <div class="grid-admin-buttons">
                                    <a href="index.php?controller=receta&action=editar&id=${receta.id}" class="btn-edit-mini">Editar</a>
                                    ${deleteForm}
                                </div>
                            ` : ''}
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    function realizarBusqueda(pagina) {
        const formData = new FormData(formFiltros);
        const params = new URLSearchParams(formData);
        params.delete('controller');
        params.delete('action');
        params.set('controller', 'api');
        params.set('action', 'recetas');
        if (pagina) params.set('pagina', pagina);

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
