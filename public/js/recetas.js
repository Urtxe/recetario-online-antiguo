document.addEventListener("DOMContentLoaded", function() {
    const formFiltros = document.getElementById('formFiltros');
    const contenedorGrid = document.getElementById('contenedor-grid');

    if (!formFiltros || !contenedorGrid) {
        return;
    }

    const inputsTexto = formFiltros.querySelectorAll('input[type="text"]');
    const selects = formFiltros.querySelectorAll('select');
    let timeout = null;

    function realizarBusqueda(pagina) {
        const formData = new FormData(formFiltros);
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
