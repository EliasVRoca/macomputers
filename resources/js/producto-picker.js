const ENTIDADES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
const escapeHtml = (valor) => String(valor ?? '').replace(/[&<>"']/g, (caracter) => ENTIDADES[caracter]);

const formatearBs = (valor) => {
    const numero = Number(valor || 0);

    return `Bs ${numero.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const iniciarProductoPicker = (root) => {
    if (!root) {
        return null;
    }

    if (root.dataset.pickerIniciado === 'true') {
        return root.productoPicker ?? null;
    }

    const input = root.querySelector('[data-picker-input]');
    const hidden = root.querySelector('[data-picker-value]');
    const lista = root.querySelector('[data-picker-lista]');
    const limpiar = root.querySelector('[data-picker-limpiar]');
    const resumen = root.querySelector('[data-picker-resumen]');
    const estado = root.querySelector('[data-picker-estado]');
    const error = root.querySelector('[data-picker-error]');

    if (!input || !hidden || !lista) {
        return null;
    }

    const url = root.dataset.url;
    const selectorSucursal = root.dataset.sucursalSelector
        ? document.querySelector(root.dataset.sucursalSelector)
        : null;
    const sucursalFija = root.dataset.sucursal ? Number(root.dataset.sucursal) : null;
    const requerido = root.dataset.pickerRequerido === 'true';
    const limpiarAlCambiarSucursal = root.dataset.pickerLimpiarSucursal !== '0';

    let resultados = [];
    let activo = -1;
    let controlador = null;
    let temporizador = null;
    let peticion = 0;

    const sucursalActual = () => {
        const valor = selectorSucursal ? selectorSucursal.value : (sucursalFija ?? '');

        return valor ? Number(valor) : null;
    };

    const abrir = () => {
        lista.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    };

    const cerrar = () => {
        lista.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        activo = -1;
    };

    const actualizarBotonLimpiar = () => {
        limpiar?.classList.toggle('hidden', input.value.trim() === '' && hidden.value === '');
    };

    const ocultarResumen = () => {
        if (!resumen) {
            return;
        }

        resumen.textContent = '';
        resumen.classList.add('hidden');
        resumen.classList.remove('text-danger', 'text-ink-subtle');
    };

    const mostrarResumen = (producto) => {
        if (!resumen) {
            return;
        }

        const partes = [
            producto.codigo,
            producto.marca,
            formatearBs(producto.precio_venta),
        ].filter(Boolean);

        const tieneStock = typeof producto.stock === 'number';

        if (tieneStock) {
            partes.push(producto.stock > 0 ? `Stock: ${producto.stock}` : 'Sin stock');
        }

        resumen.textContent = partes.join(' · ');
        resumen.classList.remove('hidden', 'text-danger', 'text-ink-subtle');
        resumen.classList.add(tieneStock && producto.stock <= 0 ? 'text-danger' : 'text-ink-subtle');
    };

    const ocultarError = () => {
        error?.classList.add('hidden');
        input.removeAttribute('aria-invalid');
    };

    const mostrarError = () => {
        error?.classList.remove('hidden');
        input.setAttribute('aria-invalid', 'true');
    };

    const marcarActivo = (indice) => {
        const opciones = lista.querySelectorAll('[data-idx]');

        if (opciones.length === 0) {
            activo = -1;
            return;
        }

        activo = ((indice % opciones.length) + opciones.length) % opciones.length;

        opciones.forEach((opcion) => {
            const esActiva = Number(opcion.dataset.idx) === activo;
            opcion.setAttribute('aria-selected', esActiva ? 'true' : 'false');

            if (esActiva) {
                input.setAttribute('aria-activedescendant', opcion.id);
                opcion.scrollIntoView({ block: 'nearest' });
            }
        });
    };

    const pintar = () => {
        if (resultados.length === 0) {
            lista.innerHTML = `<p class="px-3 py-4 text-sm text-ink-muted">Sin coincidencias para «${escapeHtml(input.value.trim())}».</p>`;
            abrir();
            estado.textContent = 'Sin coincidencias';
            return;
        }

        lista.innerHTML = resultados.map((producto, indice) => {
            const referencia = [producto.codigo, producto.marca].filter(Boolean).join(' · ');
            const tieneStock = typeof producto.stock === 'number';
            const stock = tieneStock
                ? `<span class="block text-xs ${producto.stock > 0 ? 'text-ink-subtle' : 'text-danger'}">${producto.stock > 0 ? `Stock: ${producto.stock}` : 'Sin stock'}</span>`
                : '';

            return `<div
                role="option"
                id="${lista.id}-opcion-${indice}"
                data-idx="${indice}"
                aria-selected="false"
                class="flex cursor-pointer items-start justify-between gap-3 rounded-md px-3 py-2 aria-selected:bg-surface-2"
            >
                <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-ink">${escapeHtml(producto.nombre)}</span>
                    ${referencia ? `<span class="block truncate text-xs text-ink-subtle">${escapeHtml(referencia)}</span>` : ''}
                </span>
                <span class="shrink-0 text-right">
                    <span class="block text-sm font-medium tabular-nums text-ink">${formatearBs(producto.precio_venta)}</span>
                    ${stock}
                </span>
            </div>`;
        }).join('');

        abrir();
        marcarActivo(0);
        estado.textContent = resultados.length === 1 ? '1 coincidencia' : `${resultados.length} coincidencias`;
    };

    const mostrarCargando = () => {
        lista.innerHTML = '<p class="px-3 py-4 text-sm text-ink-muted">Buscando…</p>';
        abrir();
        estado.textContent = 'Buscando productos';
    };

    const cancelarPeticion = () => {
        peticion++;

        if (controlador) {
            controlador.abort();
            controlador = null;
        }
    };

    const buscar = (termino) => {
        cancelarPeticion();
        window.clearTimeout(temporizador);

        const parametros = new URLSearchParams({ q: termino });
        const sucursalId = sucursalActual();

        if (sucursalId) {
            parametros.set('sucursal_id', String(sucursalId));
        }

        const controladorPeticion = new AbortController();
        const peticionActual = ++peticion;

        controlador = controladorPeticion;
        mostrarCargando();

        return fetch(`${url}?${parametros.toString()}`, {
            headers: { Accept: 'application/json' },
            signal: controladorPeticion.signal,
        })
            .then((respuesta) => {
                if (!respuesta.ok) {
                    throw new Error('No se pudo buscar.');
                }

                return respuesta.json();
            })
            .then((datos) => {
                if (peticionActual !== peticion) {
                    return;
                }

                resultados = Array.isArray(datos.data) ? datos.data : [];
                pintar();
            })
            .catch((errorBusqueda) => {
                if (errorBusqueda.name === 'AbortError' || peticionActual !== peticion) {
                    return;
                }

                resultados = [];
                lista.innerHTML = '<p class="px-3 py-4 text-sm text-danger">No se pudo buscar. Intentá de nuevo.</p>';
                abrir();
                estado.textContent = 'Error de búsqueda';
            })
            .finally(() => {
                if (controlador === controladorPeticion) {
                    controlador = null;
                }
            });
    };

    const aplicarSeleccion = (producto) => {
        hidden.value = producto.id;
        input.value = producto.nombre;
        resultados = [];
        ocultarError();
        mostrarResumen(producto);
        actualizarBotonLimpiar();
        cerrar();
        estado.textContent = `${producto.nombre} seleccionado`;
        input.focus();

        root.dispatchEvent(new CustomEvent('producto-seleccionado', {
            bubbles: true,
            detail: { producto },
        }));
    };

    const seleccionar = (indice) => {
        const producto = resultados[indice];

        if (!producto) {
            return;
        }

        aplicarSeleccion(producto);
    };

    const limpiarSeleccion = ({ enfocar = true } = {}) => {
        hidden.value = '';
        input.value = '';
        resultados = [];
        cancelarPeticion();
        ocultarResumen();
        actualizarBotonLimpiar();
        cerrar();
        estado.textContent = 'Selección quitada';

        if (enfocar) {
            input.focus();
        }

        root.dispatchEvent(new CustomEvent('producto-seleccionado', {
            bubbles: true,
            detail: { producto: null },
        }));
    };

    const autoSeleccionar = (termino) => {
        if (resultados.length === 0) {
            return;
        }

        const codigo = termino.toLowerCase();
        const exacto = resultados.findIndex((producto) => String(producto.codigo ?? '').toLowerCase() === codigo);

        if (exacto >= 0) {
            seleccionar(exacto);
            return;
        }

        if (resultados.length === 1) {
            seleccionar(0);
        }
    };

    input.addEventListener('focus', () => {
        if (hidden.value !== '') {
            input.select();
        }
    });

    input.addEventListener('input', () => {
        const termino = input.value.trim();

        actualizarBotonLimpiar();
        ocultarError();

        if (hidden.value !== '') {
            hidden.value = '';
            ocultarResumen();
            root.dispatchEvent(new CustomEvent('producto-seleccionado', {
                bubbles: true,
                detail: { producto: null },
            }));
        }

        window.clearTimeout(temporizador);

        if (termino.length < 2) {
            cancelarPeticion();
            resultados = [];
            cerrar();
            estado.textContent = '';
            return;
        }

        temporizador = window.setTimeout(() => buscar(termino), 250);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();

            if (resultados.length === 0) {
                const termino = input.value.trim();

                if (termino.length >= 2) {
                    buscar(termino);
                }

                return;
            }

            if (lista.classList.contains('hidden')) {
                abrir();
                marcarActivo(activo >= 0 ? activo : 0);
                return;
            }

            marcarActivo(activo + (event.key === 'ArrowDown' ? 1 : -1));
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();

            if (!lista.classList.contains('hidden') && activo >= 0) {
                seleccionar(activo);
                return;
            }

            const termino = input.value.trim();

            if (termino.length >= 2) {
                buscar(termino).then(() => autoSeleccionar(termino));
            }

            return;
        }

        if (event.key === 'Escape' && !lista.classList.contains('hidden')) {
            event.preventDefault();
            cerrar();
            return;
        }

        if (event.key === 'Tab') {
            cerrar();
        }
    });

    lista.addEventListener('mousedown', (event) => {
        event.preventDefault();
    });

    lista.addEventListener('mousemove', (event) => {
        const opcion = event.target.closest('[data-idx]');

        if (opcion && Number(opcion.dataset.idx) !== activo) {
            marcarActivo(Number(opcion.dataset.idx));
        }
    });

    lista.addEventListener('click', (event) => {
        const opcion = event.target.closest('[data-idx]');

        if (opcion) {
            seleccionar(Number(opcion.dataset.idx));
        }
    });

    limpiar?.addEventListener('click', () => limpiarSeleccion());

    document.addEventListener('pointerdown', (event) => {
        if (!root.contains(event.target)) {
            cerrar();
        }
    });

    if (limpiarAlCambiarSucursal) {
        selectorSucursal?.addEventListener('change', () => {
            limpiarSeleccion({ enfocar: false });
        });
    }

    root.closest('form')?.addEventListener('submit', (event) => {
        if (!requerido || hidden.value !== '') {
            ocultarError();

            return;
        }

        event.preventDefault();
        mostrarError();
        cerrar();

        const pickers = Array.from(event.target.querySelectorAll('[data-producto-picker]'));
        const primeroVacio = pickers.find((picker) => picker.querySelector('[data-picker-value]')?.value === '');

        if (primeroVacio === root) {
            input.focus();
        }
    });

    actualizarBotonLimpiar();
    ocultarError();

    root.dataset.pickerIniciado = 'true';
    root.productoPicker = {
        seleccionar: (producto) => {
            if (!producto) {
                limpiarSeleccion({ enfocar: false });

                return;
            }

            aplicarSeleccion(producto);
        },
        limpiar: limpiarSeleccion,
        enfocar: () => input.focus(),
    };

    return root.productoPicker;
};

window.iniciarProductoPicker = iniciarProductoPicker;

document.querySelectorAll('[data-producto-picker]').forEach(iniciarProductoPicker);

export { iniciarProductoPicker };
