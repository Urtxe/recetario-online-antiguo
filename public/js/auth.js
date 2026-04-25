document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.contenedor-auth');
    const tabControls = Array.from(document.querySelectorAll('[data-tab]'));
    const panels = Array.from(document.querySelectorAll('.panel[data-panel]'));

    if (!container || tabControls.length === 0 || panels.length === 0) {
        return;
    }

    const tabNames = ['login', 'register', 'recover'];
    const panelMap = new Map(panels.map((panel) => [panel.dataset.panel, panel]));

    const focusFirstField = (panel) => {
        const field = panel.querySelector('input:not([type="hidden"]), textarea, select');
        if (field) {
            field.focus({ preventScroll: true });
        }
    };

    const getStoredTab = () => {
        try {
            return localStorage.getItem('recetario-auth-tab');
        } catch (error) {
            return null;
        }
    };

    function setActiveTab(tab, options = {}) {
        const resolvedTab = tabNames.includes(tab) ? tab : 'login';
        const { persist = true, focus = true } = options;

        tabControls.forEach((control) => {
            const isActive = control.dataset.tab === resolvedTab;
            control.classList.toggle('activo', isActive);
            control.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            if (!panel.dataset.panel) {
                return;
            }
            panel.classList.toggle('activo', panel.dataset.panel === resolvedTab);
        });

        if (persist) {
            try {
                localStorage.setItem('recetario-auth-tab', resolvedTab);
            } catch (error) {
                // Sin storage: seguimos funcionando igual en local.
            }
        }

        if (focus && panelMap.has(resolvedTab)) {
            focusFirstField(panelMap.get(resolvedTab));
        }

        if (resolvedTab === 'recover' && panelMap.has('recover')) {
            setTimeout(() => {
                panelMap.get('recover').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 50);
        }
    }

    const hash = window.location.hash.replace('#', '');
    const storedTab = getStoredTab();
    const serverDefault = container.dataset.initialTab || 'login';
    const initialTab = hash === 'panel-recuperacion'
        ? 'recover'
        : (tabNames.includes(storedTab) ? storedTab : serverDefault);

    setActiveTab(initialTab, { persist: false });

    tabControls.forEach((control) => {
        control.addEventListener('click', (event) => {
            if (control.tagName === 'A') {
                event.preventDefault();
            }
            setActiveTab(control.dataset.tab || 'login');
        });
    });
});
