document.addEventListener('DOMContentLoaded', function () {
    const btnLogin = document.getElementById('btn-login');
    const btnRegister = document.getElementById('btn-register');
    const panelLogin = document.getElementById('panel-login');
    const panelRegistro = document.getElementById('panel-registro');
    const recoverLink = document.querySelector('[data-scroll-panel="recover"]');

    if (!btnLogin || !btnRegister || !panelLogin || !panelRegistro) {
        return;
    }

    const focusFirstField = (panel) => {
        const field = panel.querySelector('input:not([type="hidden"]), textarea, select');
        if (field) {
            field.focus({ preventScroll: true });
        }
    };

    function setActiveTab(tab) {
        const showRegister = tab === 'register';

        btnLogin.classList.toggle('activo', !showRegister);
        btnRegister.classList.toggle('activo', showRegister);
        btnLogin.setAttribute('aria-selected', showRegister ? 'false' : 'true');
        btnRegister.setAttribute('aria-selected', showRegister ? 'true' : 'false');

        panelLogin.classList.toggle('activo', !showRegister);
        panelRegistro.classList.toggle('activo', showRegister);

        try {
            localStorage.setItem('recetario-auth-tab', tab);
        } catch (error) {
            // Sin storage: seguimos funcionando igual en local.
        }

        focusFirstField(showRegister ? panelRegistro : panelLogin);
    }

    const hash = window.location.hash.replace('#', '');
    const storedTab = (() => {
        try {
            return localStorage.getItem('recetario-auth-tab');
        } catch (error) {
            return null;
        }
    })();
    const defaultTab = panelRegistro.classList.contains('activo') ? 'register' : 'login';
    const initialTab = hash === 'panel-registro' ? 'register' : (storedTab === 'register' ? 'register' : defaultTab);

    setActiveTab(initialTab);

    btnRegister.addEventListener('click', () => setActiveTab('register'));
    btnLogin.addEventListener('click', () => setActiveTab('login'));

    if (recoverLink) {
        recoverLink.addEventListener('click', () => {
            try {
                localStorage.setItem('recetario-auth-tab', 'login');
            } catch (error) {
                // No-op.
            }
        });
    }

    if (hash === 'panel-recuperacion') {
        const recoverPanel = document.getElementById('panel-recuperacion');
        if (recoverPanel) {
            setTimeout(() => recoverPanel.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
        }
    }
});
