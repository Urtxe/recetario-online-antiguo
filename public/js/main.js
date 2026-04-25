// Gestión de favoritos y modal de imagen
document.addEventListener("DOMContentLoaded", function() {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    const flashMessage = document.getElementById('flash-message');

    if (flashMessage) {
        window.setTimeout(() => {
            flashMessage.classList.add('is-hiding');
            window.setTimeout(() => {
                flashMessage.remove();
            }, 250);
        }, 3000);
    }

    document.addEventListener('submit', function(event) {
        const form = event.target.closest('form[data-confirm]');
        if (!form) {
            return;
        }

        const message = form.getAttribute('data-confirm') || '¿Seguro?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });

    // Alternar favoritos con delegación de eventos:
    // funciona aunque el grid se reemplace por AJAX o cambie de página.
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-fav, .btn-fav-ver');
        if (!btn) return;

        e.preventDefault();

        const recetaId = btn.getAttribute('data-id');
        const desdeFavoritos = btn.hasAttribute('data-from-favorites');

        fetch('index.php?controller=receta&action=favorito', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams({
                id: recetaId,
                _csrf_token: csrfToken
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (desdeFavoritos && !data.favorito) {
                        // Remover tarjeta en Mis Favoritos al quitar corazón
                        const tarjeta = btn.closest('.receta-item');
                        if (tarjeta) {
                            tarjeta.style.transition = 'opacity 0.3s';
                            tarjeta.style.opacity = '0';
                            setTimeout(() => tarjeta.remove(), 300);
                        }
                    } else {
                        // En otras páginas solo cambiar emoji
                        btn.textContent = data.favorito ? '❤️' : '🤍';
                    }
                }
            })
            .catch(err => console.error('Error:', err));
    });

    // Modal de imagen
    const modal = document.getElementById('lightbox-modal');
    const modalImg = document.getElementById('modal-img');
    const imgClickable = document.getElementById('img-zoom');
    const btnCerrar = document.querySelector('.close-modal');

    if (imgClickable) {
        imgClickable.addEventListener('click', function() {
            modalImg.src = this.src;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    // Zoom con rueda
    let escala = 1;
    if (modalImg) {
        modalImg.addEventListener('wheel', function(e) {
            e.preventDefault();
            const sensibilidad = 0.1;
            if (e.deltaY < 0) {
                escala += sensibilidad;
            } else if (escala > 1) {
                escala -= sensibilidad;
            }
            modalImg.style.transform = `scale(${escala})`;
        }, { passive: false });
    }

    // Arrastre con ratón
    let isDragging = false;
    let startX, startY;
    let translateX = 0, translateY = 0;

    if (modalImg) {
        const startDrag = (e) => {
            if (escala > 1) {
                isDragging = true;
                modalImg.style.cursor = 'grabbing';
                startX = (e.pageX || e.touches[0].pageX) - translateX;
                startY = (e.pageY || e.touches[0].pageY) - translateY;
            }
        };

        const moveImage = (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const x = (e.pageX || e.touches[0].pageX) - startX;
            const y = (e.pageY || e.touches[0].pageY) - startY;
            translateX = x;
            translateY = y;
            modalImg.style.transform = `translate(${translateX}px, ${translateY}px) scale(${escala})`;
        };

        const stopDrag = () => {
            isDragging = false;
            modalImg.style.cursor = 'grab';
        };

        modalImg.addEventListener('mousedown', startDrag);
        window.addEventListener('mousemove', moveImage);
        window.addEventListener('mouseup', stopDrag);

        modalImg.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) startDrag(e);
        });
        modalImg.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1) moveImage(e);
        });
        modalImg.addEventListener('touchend', stopDrag);
    }

    // Cerrar modal
    const cerrarModal = () => {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            escala = 1;
            translateX = 0;
            translateY = 0;
            modalImg.style.transform = `translate(0, 0) scale(1)`;
        }
    };

    if (btnCerrar) {
        btnCerrar.addEventListener('click', cerrarModal);
    }

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModal();
            }
        });
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal && modal.classList.contains('active')) {
            cerrarModal();
        }
    });

    // Zoom táctil (pellizcar)
    let distInicial = -1;
    if (modalImg) {
        modalImg.addEventListener('touchmove', function(e) {
            if (e.touches.length === 2) {
                const distActual = Math.hypot(
                    e.touches[0].pageX - e.touches[1].pageX,
                    e.touches[0].pageY - e.touches[1].pageY
                );
                if (distInicial > 0) {
                    if (distActual > distInicial) escala += 0.04;
                    else if (escala > 1) escala -= 0.04;
                    modalImg.style.transform = `scale(${escala})`;
                }
                distInicial = distActual;
            }
        }, { passive: false });

        modalImg.addEventListener('touchend', () => { distInicial = -1; });
    }
});
