// ---------------------------------------------------------------- Tema
document.addEventListener('click', (event) => {
    const boton = event.target.closest('[data-toggle-tema]');

    if (!boton) {
        return;
    }

    const oscuro = document.documentElement.classList.toggle('dark');
    localStorage.setItem('tema', oscuro ? 'oscuro' : 'claro');
});

// ---------------------------------------------------------- Confirmaciones
document.addEventListener('submit', (event) => {
    const formulario = event.target.closest('form[data-confirm]');

    if (!formulario || !formulario.dataset.confirm) {
        return;
    }

    if (!window.confirm(formulario.dataset.confirm)) {
        event.preventDefault();
    }
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

// ------------------------------------------------------------- Modales
(function () {
    document.addEventListener('click', (event) => {
        const abridor = event.target.closest('[data-modal-abrir]');
        if (abridor) {
            document.getElementById(abridor.dataset.modalAbrir)?.showModal();
            return;
        }

        const cerrador = event.target.closest('[data-modal-cerrar]');
        if (cerrador) {
            cerrador.closest('dialog')?.close();
        }
    });

    // Cierra al hacer clic fuera del contenido del modal.
    document.querySelectorAll('dialog.modal').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });
})();

// ------------------------------ Previsualización del saldo (movimiento)
(function () {
    const form = document.querySelector('[data-preview-movimiento]');
    const datos = document.getElementById('stock-datos');

    if (!form || !datos) {
        return;
    }

    const mapa = JSON.parse(datos.textContent || '{}');
    const producto = form.querySelector('[data-preview-producto]');
    const sucursal = form.querySelector('[data-preview-sucursal]');
    const tipo = form.querySelector('[data-preview-tipo]');
    const cantidad = form.querySelector('[data-preview-cantidad]');
    const salida = form.querySelector('[data-preview-salida]');

    const neutro = 'rounded-lg bg-surface-2 px-3 py-2 text-sm text-ink-muted';

    const pintar = () => {
        const pid = producto.value;
        const sid = sucursal ? sucursal.value : '';
        const n = parseInt(cantidad.value, 10);

        if (!pid || !sid || !Number.isInteger(n) || n < 0) {
            salida.textContent = 'Elige producto, sucursal y cantidad para ver el saldo resultante.';
            salida.className = neutro;
            return;
        }

        const actual = Number((mapa[pid] || {})[sid] || 0);
        let resultado = n;

        if (tipo.value === 'entrada') {
            resultado = actual + n;
        } else if (tipo.value === 'salida') {
            resultado = actual - n;
        }

        if (resultado < 0) {
            salida.textContent = `No alcanza: hay ${actual} y quieres sacar ${n}.`;
            salida.className = 'rounded-lg bg-danger-soft px-3 py-2 text-sm text-danger';
        } else {
            salida.textContent = `Saldo resultante: ${resultado} (ahora hay ${actual}).`;
            salida.className = 'rounded-lg bg-surface-2 px-3 py-2 text-sm text-ink';
        }
    };

    form.addEventListener('input', pintar);
    form.addEventListener('change', pintar);
    pintar();
})();

// ------------------------------------------- Traer de la otra sucursal
(function () {
    const dialog = document.getElementById('modal-traspaso');

    if (!dialog) {
        return;
    }

    const sucursales = JSON.parse(document.getElementById('sucursales-datos')?.textContent || '{}');
    const esGerente = dialog.dataset.gerente === '1';
    const campoProducto = dialog.querySelector('[name="producto_id"]');
    const nombre = dialog.querySelector('[data-traspaso-nombre]');
    const origen = dialog.querySelector('[name="sucursal_origen_id"]');
    const destino = dialog.querySelector('[name="sucursal_destino_id"]');

    const ajustarDestino = () => {
        if (!esGerente || !destino || destino.tagName !== 'SELECT') {
            return;
        }

        Array.from(destino.options).forEach((opcion) => {
            opcion.disabled = opcion.value === origen.value;
        });

        if (destino.value === origen.value) {
            const otra = Array.from(destino.options).find((opcion) => opcion.value !== origen.value);
            if (otra) {
                destino.value = otra.value;
            }
        }
    };

    document.addEventListener('click', (event) => {
        const boton = event.target.closest('[data-traer]');
        if (!boton) {
            return;
        }

        const info = JSON.parse(boton.dataset.traer);
        campoProducto.value = info.producto;
        nombre.textContent = info.nombre;

        origen.innerHTML = Object.entries(info.stock)
            .filter(([, stock]) => Number(stock) > 0)
            .map(([sid, stock]) => `<option value="${sid}">${sucursales[sid] ?? sid} (${stock} disp.)</option>`)
            .join('');

        ajustarDestino();
        dialog.showModal();
    });

    origen.addEventListener('change', ajustarDestino);
})();

// ------------------------------------- Filas dinámicas (formularios con ítems)
(function () {
    document.querySelectorAll('[data-filas]').forEach((contenedor) => {
        const plantilla = document.getElementById(contenedor.dataset.filas);

        if (!plantilla) {
            return;
        }

        const boton = document.querySelector('[data-agregar-fila]');
        let indice = 0;

        const agregar = () => {
            contenedor.insertAdjacentHTML('beforeend', plantilla.innerHTML.replaceAll('__IDX__', indice++));
        };

        boton?.addEventListener('click', agregar);
        contenedor.addEventListener('click', (event) => {
            if (event.target.closest('[data-quitar]')) {
                event.target.closest('.fila')?.remove();
            }
        });

        agregar();
    });
})();

// ------------------------------------------------- Landing pública
(function () {
    const overlay = document.querySelector('[data-landing-menu]');

    if (!overlay) {
        return;
    }

    const abridor = document.querySelector('[data-landing-menu-abrir]');

    const cerrar = () => {
        overlay.hidden = true;
        abridor?.setAttribute('aria-expanded', 'false');
    };

    abridor?.addEventListener('click', () => {
        overlay.hidden = false;
        abridor.setAttribute('aria-expanded', 'true');
    });

    overlay.addEventListener('click', (event) => {
        if (event.target.closest('[data-landing-menu-cerrar]') || event.target.closest('[data-landing-menu-link]')) {
            cerrar();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            cerrar();
        }
    });
})();
