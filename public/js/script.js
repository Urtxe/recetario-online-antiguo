document.addEventListener("DOMContentLoaded", function() {
    const btnIniciar = document.getElementById("btn-login");
    const btnRegistrar = document.getElementById("btn-register");
    const panelIniciar = document.getElementById("panel-login");
    const panelRegistrar = document.getElementById("panel-registro");

    if (btnIniciar && btnRegistrar && panelIniciar && panelRegistrar) {
        btnIniciar.addEventListener("click", function() {
            btnIniciar.classList.add("activo");
            btnRegistrar.classList.remove("activo");
            panelIniciar.classList.add("activo");
            panelRegistrar.classList.remove("activo");
        });

        btnRegistrar.addEventListener("click", function() {
            btnIniciar.classList.remove("activo");
            btnRegistrar.classList.add("activo");
            panelIniciar.classList.remove("activo");
            panelRegistrar.classList.add("activo");
        });
    }
});
