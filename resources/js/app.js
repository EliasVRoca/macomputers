// ---------------------------------------------------------------- Tema
document.addEventListener('click', (event) => {
    const boton = event.target.closest('[data-toggle-tema]');

    if (!boton) {
        return;
    }

    const oscuro = document.documentElement.classList.toggle('dark');
    localStorage.setItem('tema', oscuro ? 'oscuro' : 'claro');
});

// ------------------------------------------------- Panel contextual (riel)
(function () {
    const rail = document.querySelector('[data-rail]');
    const panel = document.querySelector('[data-panel]');

    if (!rail || !panel) {
        return;
    }

    const secciones = panel.querySelectorAll('[data-panel-grupo]');
    const enlaces = rail.querySelectorAll('[data-grupo]');
    const grupoActivo = rail.querySelector('[data-grupo][data-activo="1"]')?.dataset.grupo;

    const mostrar = (nombre) => {
        secciones.forEach((seccion) => {
            seccion.hidden = seccion.dataset.panelGrupo !== nombre;
        });
    };

    enlaces.forEach((enlace) => {
        enlace.addEventListener('mouseenter', () => mostrar(enlace.dataset.grupo));
        enlace.addEventListener('focus', () => mostrar(enlace.dataset.grupo));
    });

    const volver = () => grupoActivo && mostrar(grupoActivo);
    rail.addEventListener('mouseleave', volver);
    panel.addEventListener('mouseleave', volver);
})();

// ------------------------------------------------------ Navegación móvil
(function () {
    const overlay = document.querySelector('[data-menu-movil]');

    if (!overlay) {
        return;
    }

    document.querySelector('[data-menu-abrir]')?.addEventListener('click', () => {
        overlay.hidden = false;
    });

    overlay.addEventListener('click', (event) => {
        if (event.target.closest('[data-menu-cerrar]')) {
            overlay.hidden = true;
        }
    });
})();

// -------------------------------------------------- Menú de cuenta
(function () {
    const abiertos = () => document.querySelectorAll('details[data-cuenta][open]');

    const cerrar = () => abiertos().forEach((detalle) => { detalle.open = false; });

    document.addEventListener('click', (event) => {
        abiertos().forEach((detalle) => {
            if (!detalle.contains(event.target)) {
                detalle.open = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            cerrar();
        }
    });
})();

// ----------------------------------------------- Paleta de comandos (Ctrl K)
(function () {
    const dialog = document.getElementById('paleta');
    const datos = document.getElementById('paleta-datos');

    if (!dialog || !datos) {
        return;
    }

    const input = dialog.querySelector('[data-paleta-input]');
    const lista = dialog.querySelector('[data-paleta-lista]');
    const items = JSON.parse(datos.textContent);
    let filtrados = items;
    let indice = 0;

    const pintar = () => {
        if (filtrados.length === 0) {
            lista.innerHTML = '<p class="px-3 py-8 text-center text-sm text-ink-muted">Sin resultados.</p>';
            return;
        }

        lista.innerHTML = filtrados.map((item, n) => `
            <a href="${item.url}" class="paleta-item ${n === indice ? 'activa' : ''}" data-idx="${n}">
                <span class="flex-1 truncate">${item.label}</span>
                <span class="shrink-0 text-xs ${item.grupo ? 'text-ink-tertiary' : 'text-brand'}">${item.grupo ?? 'Acción'}</span>
            </a>`).join('');

        lista.querySelector('.activa')?.scrollIntoView({ block: 'nearest' });
    };

    const filtrar = () => {
        const q = input.value.trim().toLowerCase();
        filtrados = items.filter((item) =>
            item.label.toLowerCase().includes(q) || (item.grupo ?? '').toLowerCase().includes(q));
        indice = 0;
        pintar();
    };

    const mover = (delta) => {
        if (filtrados.length === 0) {
            return;
        }
        indice = (indice + delta + filtrados.length) % filtrados.length;
        pintar();
    };

    const abrir = () => {
        input.value = '';
        filtrar();
        dialog.showModal();
        input.focus();
    };

    document.querySelector('[data-paleta-abrir]')?.addEventListener('click', abrir);

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            abrir();
        }
    });

    input.addEventListener('input', filtrar);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            mover(1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            mover(-1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const activo = filtrados[indice];
            if (activo) {
                window.location.href = activo.url;
            }
        }
    });

    lista.addEventListener('mousemove', (event) => {
        const item = event.target.closest('[data-idx]');
        if (item && Number(item.dataset.idx) !== indice) {
            indice = Number(item.dataset.idx);
            pintar();
        }
    });
})();
