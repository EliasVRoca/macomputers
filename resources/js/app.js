// Cambio de tema claro/oscuro. La preferencia se guarda en localStorage.
document.addEventListener('click', (event) => {
    const boton = event.target.closest('[data-toggle-tema]');

    if (!boton) {
        return;
    }

    const esOscuro = document.documentElement.classList.toggle('dark');
    localStorage.setItem('tema', esOscuro ? 'oscuro' : 'claro');
});
