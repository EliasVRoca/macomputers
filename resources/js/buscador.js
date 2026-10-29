const ICON_PACKAGE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-10" aria-hidden="true"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><path d="m3.3 7 7.7 4.7a2 2 0 0 0 2 0L20.7 7"/></svg>';
const ICON_TRANSFER = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M8 3 4 7l4 4"/><path d="M4 7h16"/><path d="m16 21 4-4-4-4"/><path d="M20 17H4"/></svg>';
const ICON_CART = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>';

const ENTIDADES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
const escapeHtml = (valor) => String(valor ?? '').replace(/[&<>"']/g, (caracter) => ENTIDADES[caracter]);
const escapeAttr = (valor) => escapeHtml(valor);

const formatearBs = (valor) => {
    const numero = Number(valor || 0);

    return `Bs ${numero.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const iniciarBuscador = () => {
    const root = document.querySelector('[data-buscador]');

    if (!root) {
        return;
    }

    const input = root.querySelector('#buscador-termino');
    const botonesLimpiar = root.querySelectorAll('[data-buscador-limpiar]');
    const resultados = root.querySelector('[data-buscador-resultados]');
    const cargando = root.querySelector('[data-buscador-cargando]');
    const vacio = root.querySelector('[data-buscador-vacio]');
    const vacioTermino = root.querySelector('[data-vacio-termino]');
    const similares = root.querySelector('[data-buscador-similares]');
    const similaresGrid = root.querySelector('[data-similares]');
    const error = root.querySelector('[data-buscador-error]');
    const resumen = root.querySelector('[data-buscador-resumen]');
    const conteo = root.querySelector('[data-buscador-conteo]');
    const resultado = root.querySelector('[data-buscador-resultado]');
    const estado = root.querySelector('[data-buscador-estado]');
    const sugerencias = root.querySelector('[data-buscador-sugerencias]');
    const recientes = root.querySelector('[data-buscador-recientes]');
    const recientesLista = root.querySelector('[data-buscador-recientes-lista]');
    const ctaEncargo = root.querySelector('[data-cta-encargo]');
    const ctaCompra = root.querySelector('[data-cta-compra]');
    const ctaDisponibilidad = root.querySelector('[data-cta-disponibilidad]');
    const botonesModo = root.querySelectorAll('[data-modo]');
    const botonPreguntar = root.querySelector('[data-buscador-preguntar]');
    const iaRespuesta = root.querySelector('[data-buscador-ia-respuesta]');
    const iaTexto = root.querySelector('[data-ia-texto]');
    const iaPensando = root.querySelector('[data-buscador-ia-pensando]');
    const iaAlternativas = root.querySelector('[data-buscador-ia-alternativas]');
    const iaAlternativasGrid = root.querySelector('[data-ia-alternativas]');

    const url = root.dataset.url;
    const iaUrl = root.dataset.iaUrl;
    const iaHabilitada = root.dataset.iaHabilitado === '1';
    const sucursalUsuario = Number(root.dataset.sucursal || 0);
    const puedeTraspasar = root.dataset.puedeTraspasar === '1';
    const puedeVerFicha = root.dataset.puedeVerFicha === '1';
    const puedeVender = root.dataset.puedeVender === '1';
    const CLAVE_RECIENTES = 'buscador-recientes';
    const CLAVE_MODO = 'buscador-modo';
    const CLAVE_SESION_IA = 'buscador-ia-sesion';

    let controlador = null;
    let temporizador = null;
    let sucursalesMap = {};
    let modo = iaHabilitada && localStorage.getItem(CLAVE_MODO) === 'ia' ? 'ia' : 'clasico';

    const filtros = () => {
        const datos = {};

        root.querySelectorAll('[data-filtro]').forEach((campo) => {
            if (campo.type === 'checkbox') {
                if (campo.checked) {
                    datos[campo.name] = '1';
                }
            } else if (campo.value !== '') {
                datos[campo.name] = campo.value;
            }
        });

        return datos;
    };

    const parametros = (termino) => {
        const params = new URLSearchParams();
        params.set('termino', termino);

        Object.entries(filtros()).forEach(([clave, valor]) => params.set(clave, valor));

        return params;
    };

    const actualizarUrl = (termino) => {
        const destino = new URL(window.location.href);

        if (termino !== '') {
            destino.searchParams.set('termino', termino);
        } else {
            destino.searchParams.delete('termino');
        }

        const activos = filtros();

        ['categoria_id', 'marca', 'condicion', 'sucursal_id', 'solo_stock'].forEach((clave) => {
            if (clave in activos) {
                destino.searchParams.set(clave, activos[clave]);
            } else {
                destino.searchParams.delete(clave);
            }
        });

        window.history.replaceState({}, '', destino.toString());
    };

    const actualizarBotonLimpiar = () => {
        botonesLimpiar.forEach((boton) => boton.classList.toggle('invisible', input.value.trim() === ''));
    };

    const leerRecientes = () => {
        try {
            return JSON.parse(localStorage.getItem(CLAVE_RECIENTES) || '[]');
        } catch {
            return [];
        }
    };

    const guardarReciente = (termino) => {
        const lista = [termino, ...leerRecientes().filter((item) => item !== termino)].slice(0, 6);
        localStorage.setItem(CLAVE_RECIENTES, JSON.stringify(lista));
        renderRecientes();
    };

    const usarSugerencia = (termino) => {
        input.value = termino;
        actualizarBotonLimpiar();
        input.focus();

        if (modo === 'ia') {
            preguntar();
        } else {
            buscar(termino);
        }
    };

    const aplicarModo = () => {
        botonesModo.forEach((boton) => {
            const activo = boton.dataset.modo === modo;
            boton.classList.toggle('activo', activo);
            boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });

        root.querySelectorAll('[data-hint-modo]').forEach((parrafo) => {
            parrafo.hidden = parrafo.dataset.hintModo !== modo;
        });

        root.querySelectorAll('[data-sugerencia-modo]').forEach((boton) => {
            boton.hidden = boton.dataset.sugerenciaModo !== modo;
        });

        input.placeholder = modo === 'ia'
            ? 'Ej.: estoy buscando un teclado inalámbrico para la oficina…'
            : 'Ej.: teclado 60%, laptop lenovo, RAM 8GB…';

        if (botonPreguntar) {
            botonPreguntar.hidden = modo !== 'ia';
        }
    };

    const cambiarModo = (nuevoModo) => {
        if (nuevoModo === modo) {
            return;
        }

        modo = nuevoModo;
        localStorage.setItem(CLAVE_MODO, modo);
        window.clearTimeout(temporizador);

        if (controlador) {
            controlador.abort();
            controlador = null;
        }

        resultados.innerHTML = '';
        resumen.hidden = true;
        cargando.hidden = true;
        vacio.hidden = true;
        error.hidden = true;
        similares.hidden = true;
        iaRespuesta.hidden = true;
        iaPensando.hidden = true;
        iaAlternativas.hidden = true;
        iaAlternativasGrid.innerHTML = '';
        sugerencias.hidden = input.value.trim() !== '';
        aplicarModo();
        renderRecientes();
        estado.textContent = modo === 'ia' ? 'Modo IA: escribí tu pregunta.' : 'Escribí para buscar.';
        input.focus();
    };

    const obtenerSesionIa = () => {
        let sesion = localStorage.getItem(CLAVE_SESION_IA);

        if (!sesion) {
            sesion = crypto.randomUUID();
            localStorage.setItem(CLAVE_SESION_IA, sesion);
        }

        return sesion;
    };

    const renderRecientes = () => {
        const lista = leerRecientes();
        recientes.hidden = lista.length === 0 || input.value.trim() !== '';

        recientesLista.innerHTML = lista
            .map((termino) => `<button type="button" class="buscador-sugerencia" data-sugerencia="${escapeAttr(termino)}">${escapeHtml(termino)}</button>`)
            .join('');

        recientesLista.querySelectorAll('[data-sugerencia]').forEach((boton) => {
            boton.addEventListener('click', () => usarSugerencia(boton.dataset.sugerencia));
        });
    };

    const mostrarInicio = () => {
        if (controlador) {
            controlador.abort();
            controlador = null;
        }

        resultados.innerHTML = '';
        resumen.hidden = true;
        cargando.hidden = true;
        vacio.hidden = true;
        error.hidden = true;
        similares.hidden = true;
        iaRespuesta.hidden = true;
        iaPensando.hidden = true;
        iaAlternativas.hidden = true;
        iaAlternativasGrid.innerHTML = '';
        sugerencias.hidden = false;
        actualizarBotonLimpiar();
        renderRecientes();
        estado.textContent = modo === 'ia' ? 'Modo IA: escribí tu pregunta.' : 'Escribí para buscar.';
    };

    const limpiar = () => {
        window.clearTimeout(temporizador);
        input.value = '';
        actualizarUrl('');
        mostrarInicio();
        input.focus();
    };

    const actualizarCta = (termino) => {
        const asignar = (enlace, clave) => {
            if (!enlace) {
                return;
            }

            const destino = new URL(enlace.href, window.location.origin);
            destino.searchParams.set(clave, termino);
            enlace.href = destino.toString();
        };

        asignar(ctaEncargo, 'descripcion');
        asignar(ctaCompra, 'descripcion');
        asignar(ctaDisponibilidad, 'buscar');
    };

    const prepararTraer = (contenedor) => {
        contenedor.querySelectorAll('[data-traer-info]').forEach((boton) => {
            let stock = {};

            try {
                stock = JSON.parse(boton.dataset.stock || '{}');
            } catch {
                stock = {};
            }

            boton.dataset.traer = JSON.stringify({
                producto: Number(boton.dataset.producto),
                nombre: boton.dataset.nombre,
                stock,
            });

            boton.removeAttribute('data-traer-info');
        });
    };

    const renderProducto = (producto, compacto = false) => {
        const clasesCondicion = {
            nuevo: 'badge-ok',
            remanufacturado: 'badge-warn',
            seminuevo: 'badge-neutral',
        };
        const claseCondicion = clasesCondicion[producto.condicion] ?? 'badge-neutral';
        const badgeStock = producto.total === 0
            ? '<span class="badge badge-danger">Sin stock</span>'
            : (producto.bajo_minimo ? '<span class="badge badge-danger">Bajo mínimo</span>' : '');
        const media = producto.imagen
            ? `<img src="${escapeAttr(producto.imagen)}" alt="${escapeAttr(producto.nombre)}" loading="lazy">`
            : `<div class="flex h-full items-center justify-center text-ink-tertiary">${ICON_PACKAGE}</div>`;
        const chips = Object.entries(producto.stock || {}).map(([sucursalId, cantidad]) => {
            const local = Number(sucursalId) === sucursalUsuario;
            const cero = Number(cantidad) === 0;
            const nombreSucursal = sucursalesMap[sucursalId] ?? `Sucursal ${sucursalId}`;

            return `<span class="stock-chip ${local ? 'local' : ''} ${cero ? 'cero' : ''}" title="${escapeAttr(nombreSucursal)}">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                ${escapeHtml(nombreSucursal)} <span class="tabular-nums">${cantidad}</span>
            </span>`;
        }).join('');
        const badgeLocal = sucursalUsuario > 0
            ? (producto.local > 0
                ? `<span class="badge badge-ok">${producto.local} en mi sucursal</span>`
                : '<span class="badge badge-warn">Sin stock local</span>')
            : `<span class="badge badge-neutral">${producto.total} en total</span>`;
        const puedeTraer = puedeTraspasar && producto.local === 0 && producto.total > 0;
        const botonTraer = puedeTraer
            ? `<button type="button" class="btn btn-secondary btn-sm" data-traer-info data-producto="${producto.id}" data-nombre="${escapeAttr(producto.nombre)}" data-stock="${escapeAttr(JSON.stringify(producto.stock || {}))}">${ICON_TRANSFER} Traer</button>`
            : '';
        const botonVender = puedeVender && producto.venta_url
            ? `<a href="${escapeAttr(producto.venta_url)}" class="btn btn-primary btn-sm">${ICON_CART} Vender</a>`
            : '';
        const botonFicha = puedeVerFicha && producto.ficha_url
            ? `<a href="${escapeAttr(producto.ficha_url)}" class="btn btn-ghost btn-sm">Ver ficha</a>`
            : '';
        const botonDisponibilidad = `<a href="${escapeAttr(producto.disponibilidad_url)}" class="btn btn-ghost btn-sm">Disponibilidad</a>`;
        const atributos = compacto
            ? ''
            : Object.entries(producto.atributos || {}).slice(0, 3)
                .map(([clave, valor]) => `<span class="badge badge-neutral">${escapeHtml(clave)}: ${escapeHtml(valor)}</span>`)
                .join('');

        return `<article class="producto-card group">
            <div class="producto-media">
                ${media}
                <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                    <span class="badge ${claseCondicion}">${escapeHtml(producto.condicion_etiqueta)}</span>
                    ${badgeStock}
                </div>
            </div>
            <div class="flex flex-1 flex-col gap-3 p-4">
                <div>
                    <p class="line-clamp-2 font-medium leading-snug">${escapeHtml(producto.nombre)}</p>
                    <p class="mt-1 font-mono text-xs text-ink-muted">${escapeHtml(producto.codigo)} · ${escapeHtml(producto.marca)}${producto.modelo ? ' · ' + escapeHtml(producto.modelo) : ''}</p>
                    <p class="text-xs text-ink-tertiary">${escapeHtml(producto.categoria ?? '')}</p>
                </div>
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-lg font-medium tabular-nums">${formatearBs(producto.precio_venta)}</p>
                        <p class="text-xs text-ink-muted">${producto.total} en stock total</p>
                    </div>
                    ${badgeLocal}
                </div>
                <div class="stock-chips">${chips}</div>
                ${atributos ? `<div class="flex flex-wrap gap-1.5">${atributos}</div>` : ''}
                <div class="mt-auto flex flex-wrap gap-2 border-t border-hairline-soft pt-3">
                    ${botonVender}
                    ${botonTraer}
                    ${botonDisponibilidad}
                    ${botonFicha}
                </div>
            </div>
        </article>`;
    };

    const render = (datos) => {
        cargando.hidden = true;
        error.hidden = true;
        sucursalesMap = Object.fromEntries((datos.sucursales || []).map((sucursal) => [String(sucursal.id), sucursal.nombre]));

        guardarReciente(datos.termino);

        if (datos.total > 0) {
            resumen.hidden = false;
            vacio.hidden = true;
            conteo.textContent = `${datos.total} ${datos.total === 1 ? 'resultado' : 'resultados'} para «${datos.termino}»`;
            resultado.textContent = datos.resultado === 'encontrado'
                ? 'Con stock disponible.'
                : (datos.resultado === 'sin_stock' ? 'Sin stock en este momento.' : 'No está en el catálogo.');
            resultados.innerHTML = datos.productos.map((producto) => renderProducto(producto)).join('');
            prepararTraer(resultados);
            estado.textContent = `${datos.total} resultados encontrados.`;

            return;
        }

        resumen.hidden = true;
        resultados.innerHTML = '';
        vacio.hidden = false;
        vacioTermino.textContent = datos.termino;
        actualizarCta(datos.termino);

        const haySimilares = (datos.similares || []).length > 0;
        similares.hidden = !haySimilares;
        similaresGrid.innerHTML = haySimilares
            ? datos.similares.map((producto) => renderProducto(producto, true)).join('')
            : '';

        if (haySimilares) {
            prepararTraer(similaresGrid);
        }

        estado.textContent = 'Sin resultados.';
    };

    const renderIa = (datos) => {
        cargando.hidden = true;
        iaPensando.hidden = true;
        error.hidden = true;
        sucursalesMap = Object.fromEntries((datos.sucursales || []).map((sucursal) => [String(sucursal.id), sucursal.nombre]));

        guardarReciente(datos.termino);

        const respuesta = String(datos.respuesta || '').trim();

        if (respuesta !== '') {
            iaTexto.textContent = respuesta;
            iaRespuesta.hidden = false;
        } else {
            iaRespuesta.hidden = true;
        }

        const hayProductos = (datos.productos || []).length > 0;

        if (hayProductos) {
            resumen.hidden = false;
            vacio.hidden = true;
            conteo.textContent = `${datos.productos.length} ${datos.productos.length === 1 ? 'resultado sugerido' : 'resultados sugeridos'} por la IA`;
            resultado.textContent = 'Respondido por el asistente.';
            resultados.innerHTML = datos.productos.map((producto) => renderProducto(producto)).join('');
            prepararTraer(resultados);
        } else {
            resumen.hidden = true;
            resultados.innerHTML = '';
            vacio.hidden = false;
            vacioTermino.textContent = datos.termino;
            actualizarCta(datos.termino);
            similares.hidden = true;
            similaresGrid.innerHTML = '';
        }

        const hayAlternativas = (datos.alternativas || []).length > 0;
        iaAlternativas.hidden = !hayAlternativas;
        iaAlternativasGrid.innerHTML = hayAlternativas
            ? datos.alternativas.map((producto) => renderProducto(producto, true)).join('')
            : '';

        if (hayAlternativas) {
            prepararTraer(iaAlternativasGrid);
        }

        estado.textContent = hayProductos
            ? `${datos.productos.length} resultados sugeridos.`
            : 'El asistente no encontró coincidencias.';
    };

    const preguntar = () => {
        const termino = String(input.value || '').trim();

        if (termino.length < 4) {
            mostrarInicio();
            estado.textContent = 'La pregunta debe tener al menos 4 caracteres.';

            return;
        }

        actualizarUrl(termino);
        sugerencias.hidden = termino !== '';
        recientes.hidden = termino !== '' || leerRecientes().length === 0;

        if (controlador) {
            controlador.abort();
        }

        controlador = new AbortController();
        iaPensando.hidden = false;
        iaRespuesta.hidden = true;
        iaAlternativas.hidden = true;
        iaAlternativasGrid.innerHTML = '';
        resultados.innerHTML = '';
        resumen.hidden = true;
        vacio.hidden = true;
        error.hidden = true;
        similares.hidden = true;
        cargando.hidden = true;
        estado.textContent = 'Preguntando a la IA…';

        fetch(`${iaUrl}?${parametros(termino).toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Buscador-Sesion': obtenerSesionIa(),
            },
            signal: controlador.signal,
            cache: 'no-store',
        })
            .then(async (respuesta) => {
                if (!respuesta.ok) {
                    let mensaje = 'No se pudo consultar la IA. Intentá de nuevo.';

                    try {
                        const datos = await respuesta.json();
                        mensaje = datos.message || mensaje;
                    } catch {
                        // Sin cuerpo JSON: se usa el mensaje genérico.
                    }

                    throw new Error(mensaje);
                }

                return respuesta.json();
            })
            .then((datos) => {
                controlador = null;
                renderIa(datos);
            })
            .catch((excepcion) => {
                if (excepcion.name === 'AbortError') {
                    return;
                }

                controlador = null;
                iaPensando.hidden = true;
                cargando.hidden = true;
                error.hidden = false;
                error.textContent = excepcion.message || 'No se pudo consultar la IA.';
                estado.textContent = 'Error del asistente.';
            });
    };

    const buscar = (valor) => {
        const termino = String(valor || '').trim();

        actualizarBotonLimpiar();
        actualizarUrl(termino);
        sugerencias.hidden = termino !== '';
        recientes.hidden = termino !== '' || leerRecientes().length === 0;

        if (termino.length < 2) {
            mostrarInicio();

            return;
        }

        if (controlador) {
            controlador.abort();
        }

        controlador = new AbortController();
        cargando.hidden = false;
        resultados.innerHTML = '';
        resumen.hidden = true;
        vacio.hidden = true;
        error.hidden = true;
        similares.hidden = true;
        iaRespuesta.hidden = true;
        iaPensando.hidden = true;
        iaAlternativas.hidden = true;
        iaAlternativasGrid.innerHTML = '';
        estado.textContent = 'Buscando…';

        fetch(`${url}?${parametros(termino).toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controlador.signal,
            cache: 'no-store',
        })
            .then(async (respuesta) => {
                if (!respuesta.ok) {
                    if (respuesta.status === 422) {
                        const datos = await respuesta.json();
                        const mensaje = Object.values(datos.errors || {}).flat()[0];

                        throw new Error(mensaje || 'Revisá el término de búsqueda.');
                    }

                    throw new Error('No se pudo buscar. Intentá de nuevo.');
                }

                return respuesta.json();
            })
            .then((datos) => {
                controlador = null;
                render(datos);
            })
            .catch((excepcion) => {
                if (excepcion.name === 'AbortError') {
                    return;
                }

                controlador = null;
                cargando.hidden = true;
                error.hidden = false;
                error.textContent = excepcion.message || 'No se pudo buscar.';
                estado.textContent = 'Error de búsqueda.';
            });
    };

    input.addEventListener('input', () => {
        window.clearTimeout(temporizador);
        actualizarBotonLimpiar();
        sugerencias.hidden = input.value.trim() !== '';
        recientes.hidden = input.value.trim() !== '' || leerRecientes().length === 0;

        if (modo === 'ia') {
            return;
        }

        temporizador = window.setTimeout(() => buscar(input.value), 350);
    });

    input.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            window.clearTimeout(temporizador);

            if (modo === 'ia') {
                preguntar();
            } else {
                buscar(input.value);
            }
        }

        if (evento.key === 'Escape') {
            evento.preventDefault();
            limpiar();
        }
    });

    botonesLimpiar.forEach((boton) => boton.addEventListener('click', limpiar));

    botonesModo.forEach((boton) => {
        boton.addEventListener('click', () => cambiarModo(boton.dataset.modo === 'ia' ? 'ia' : 'clasico'));
    });

    if (botonPreguntar) {
        botonPreguntar.addEventListener('click', preguntar);
    }

    document.querySelectorAll('[data-sugerencia]').forEach((boton) => {
        boton.addEventListener('click', () => usarSugerencia(boton.dataset.sugerencia));
    });

    root.querySelectorAll('[data-filtro]').forEach((campo) => {
        campo.addEventListener('change', () => {
            if (input.value.trim().length >= 2 && modo !== 'ia') {
                buscar(input.value);
            } else {
                actualizarUrl(input.value.trim());
            }
        });
    });

    document.addEventListener('keydown', (evento) => {
        const activo = document.activeElement?.tagName;
        const escribiendo = ['INPUT', 'TEXTAREA', 'SELECT'].includes(activo);

        if (evento.key === '/' && !escribiendo) {
            evento.preventDefault();
            input.focus();
            input.select();
        }
    });

    renderRecientes();
    aplicarModo();
    actualizarBotonLimpiar();

    const inicial = input.value.trim();

    if (inicial.length >= 2 && modo !== 'ia') {
        buscar(inicial);
    } else {
        mostrarInicio();
    }

    input.focus();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarBuscador);
} else {
    iniciarBuscador();
}
