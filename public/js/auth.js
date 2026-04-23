document.addEventListener("DOMContentLoaded", function() {
    const btnLogin = document.getElementById('btn-login');
    const btnRegister = document.getElementById('btn-register');
    const panelLogin = document.getElementById('panel-login');
    const panelRegistro = document.getElementById('panel-registro');

    function switchTab(showRegister) {
        if (showRegister) {
            btnRegister.classList.add('activo');
            panelRegistro.classList.add('activo');
            btnLogin.classList.remove('activo');
            panelLogin.classList.remove('activo');
        } else {
            btnLogin.classList.add('activo');
            panelLogin.classList.add('activo');
            btnRegister.classList.remove('activo');
            panelRegistro.classList.remove('activo');
        }
    }

    btnRegister.addEventListener('click', () => switchTab(true));
    btnLogin.addEventListener('click', () => switchTab(false));
});
