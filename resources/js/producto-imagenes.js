(function () {
    const dialog = document.getElementById('modal-imagenes-internet');

    if (!dialog) {
        return;
    }

    const consulta = dialog.querySelector('[data-imagenes-consulta]');
    const botonBuscar = dialog.querySelector('[data-imagenes-buscar]');
    const resultados = dialog.querySelector('[data-imagenes-resultados]');
    const estado = dialog.querySelector('[data-imagenes-estado]');
    const contador = dialog.querySelector('[data-imagenes-contador]');
    const agregar = dialog.querySelector('[data-imagenes-agregar]');
    const principal = dialog.querySelector('[data-imagenes-principal]');
    const atribucion = dialog.querySelector('[data-imagenes-atribucion]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const urlBuscar = dialog.dataset.urlBuscar;
    const urlGuardar = dialog.dataset.urlGuardar;
    const seleccionados = new Map();
    let controlador = null;

    const setEstado = (texto, error = false) => {
        estado.textContent = texto;
        estado.className = error ? 'text-xs text-danger' : 'text-xs text-ink-muted';
    };

    const actualizarSeleccion = () => {
        const total = seleccionados.size;
        contador.textContent = `${total} ${total === 1 ? 'seleccionada' : 'seleccionadas'}`;
        agregar.disabled = total === 0;
    };

    const limpiarResultados = () => {
        resultados.replaceChildren();
    };

    const mostrarEsqueletos = () => {
        limpiarResultados();

        for (let i = 0; i < 8; i++) {
            const bloque = document.createElement('div');
            bloque.className = 'skeleton aspect-[4/3]';
            resultados.append(bloque);
        }
    };

    const alternar = (resultado, boton, check) => {
        if (seleccionados.has(resultado.url)) {
            seleccionados.delete(resultado.url);
            boton.setAttribute('aria-pressed', 'false');
            boton.classList.remove('ring-2', 'ring-brand');
            check.classList.add('hidden');
            setEstado('');
        } else if (seleccionados.size >= 5) {
            setEstado('Podés elegir hasta 5 imágenes.', true);
            return;
        } else {
            seleccionados.set(resultado.url, resultado);
            boton.setAttribute('aria-pressed', 'true');
            boton.classList.add('ring-2', 'ring-brand');
            check.classList.remove('hidden');
            setEstado('');
        }

        actualizarSeleccion();
    };

    const crearTarjeta = (resultado) => {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'resultado-imagen group relative flex flex-col overflow-hidden rounded-lg border border-hairline bg-surface-1 text-left transition-colors hover:border-ink-tertiary';
        boton.dataset.url = resultado.url;
        boton.setAttribute('aria-pressed', 'false');

        const media = document.createElement('div');
        media.className = 'relative aspect-[4/3] overflow-hidden bg-surface-2';

        const imagen = document.createElement('img');
        imagen.src = resultado.miniatura;
        imagen.alt = resultado.titulo || 'Imagen';
        imagen.loading = 'lazy';
        imagen.decoding = 'async';
        imagen.referrerPolicy = 'no-referrer';
        imagen.className = 'h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]';
        imagen.addEventListener('error', () => {
            imagen.remove();
            media.classList.add('flex', 'items-center', 'justify-center');

            const sinVista = document.createElement('span');
            sinVista.className = 'px-2 text-center text-[11px] text-ink-tertiary';
            sinVista.textContent = 'Sin vista previa';
            media.append(sinVista);
        });

        const check = document.createElement('span');
        check.className = 'absolute top-2 right-2 hidden h-6 w-6 items-center justify-center rounded-full bg-brand text-xs font-semibold text-on-primary shadow';
        check.setAttribute('aria-hidden', 'true');
        check.textContent = '✓';

        media.append(imagen, check);

        const info = document.createElement('div');
        info.className = 'space-y-0.5 p-2';

        const titulo = document.createElement('p');
        titulo.className = 'line-clamp-2 text-[11px] leading-tight font-medium text-ink';
        titulo.textContent = resultado.titulo || 'Imagen';

        const fuente = document.createElement('p');
        fuente.className = 'truncate text-[11px] text-ink-subtle';
        fuente.textContent = resultado.fuente || 'Fuente desconocida';

        const detalle = document.createElement('p');
        detalle.className = 'truncate text-[11px] text-ink-subtle';
        detalle.textContent = [
            resultado.ancho && resultado.alto ? `${resultado.ancho}×${resultado.alto}` : null,
            resultado.licencia,
        ].filter(Boolean).join(' · ');

        info.append(titulo, fuente, detalle);
        boton.append(media, info);
        boton.addEventListener('click', () => alternar(resultado, boton, check));

        return boton;
    };

    const buscar = async () => {
        const q = consulta.value.trim();

        if (q.length < 2) {
            setEstado('Escribí al menos 2 caracteres.', true);
            consulta.focus();
            return;
        }

        controlador?.abort();

        const local = new AbortController();
        controlador = local;

        seleccionados.clear();
        actualizarSeleccion();
        mostrarEsqueletos();
        setEstado('Buscando imágenes…');
        botonBuscar.disabled = true;

        try {
            const respuesta = await fetch(`${urlBuscar}?q=${encodeURIComponent(q)}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: local.signal,
                cache: 'no-store',
            });

            const datos = await respuesta.json().catch(() => ({}));

            if (!respuesta.ok) {
                throw new Error(datos.message || 'No se pudo buscar imágenes.');
            }

            if (datos.proveedor?.etiqueta) {
                atribucion.textContent = datos.proveedor.etiqueta;
                atribucion.href = datos.proveedor.url || '#';
            }

            limpiarResultados();

            if (!Array.isArray(datos.resultados) || datos.resultados.length === 0) {
                setEstado('No encontramos imágenes. Probá con otra búsqueda.', true);
                return;
            }

            setEstado(`${datos.resultados.length} resultados. Elegí hasta 5.`);

            datos.resultados.forEach((resultado) => {
                resultados.append(crearTarjeta(resultado));
            });
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            limpiarResultados();
            setEstado(error.message || 'No se pudo buscar imágenes. Probá de nuevo.', true);
        } finally {
            if (controlador === local) {
                botonBuscar.disabled = false;
            }
        }
    };

    const agregarSeleccionadas = async () => {
        if (seleccionados.size === 0) {
            return;
        }

        const lista = [...seleccionados.values()];
        const total = lista.length;
        let agregadas = 0;
        const errores = [];

        agregar.disabled = true;
        botonBuscar.disabled = true;

        for (let indice = 0; indice < total; indice++) {
            const resultado = lista[indice];
            setEstado(`Agregando ${indice + 1} de ${total}…`);

            try {
                const respuesta = await fetch(urlGuardar, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        url: resultado.url,
                        miniatura: resultado.miniatura,
                        origen_url: resultado.origen_url,
                        proveedor: resultado.proveedor,
                        titulo: resultado.titulo,
                        autor: resultado.autor,
                        licencia: resultado.licencia,
                        licencia_url: resultado.licencia_url,
                        es_principal: principal.checked && indice === 0,
                    }),
                });

                const datos = await respuesta.json().catch(() => ({}));

                if (!respuesta.ok) {
                    throw new Error(datos.message || 'No se pudo agregar la imagen.');
                }

                agregadas++;
            } catch (error) {
                errores.push(error.message);
            }
        }

        if (agregadas > 0) {
            setEstado(`Listo: ${agregadas} ${agregadas === 1 ? 'imagen agregada' : 'imágenes agregadas'}. Recargando…`);
            window.location.reload();
            return;
        }

        agregar.disabled = false;
        botonBuscar.disabled = false;
        setEstado(errores[0] || 'No se pudo agregar la imagen.', true);
    };

    const abrirModal = () => {
        if (dialog.open) {
            return;
        }

        document.body.classList.add('overflow-hidden');
        dialog.showModal();
        consulta.focus();

        if (resultados.childElementCount === 0) {
            buscar();
        }
    };

    document.querySelectorAll('[data-imagenes-abrir]').forEach((boton) => {
        boton.addEventListener('click', abrirModal);
    });

    botonBuscar.addEventListener('click', buscar);
    agregar.addEventListener('click', agregarSeleccionadas);
    consulta.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            buscar();
        }
    });

    dialog.addEventListener('close', () => {
        controlador?.abort();
        controlador = null;
        botonBuscar.disabled = false;
        document.body.classList.remove('overflow-hidden');
    });

    if (new URLSearchParams(window.location.search).get('imagenes') === '1') {
        abrirModal();

        const limpia = new URL(window.location.href);
        limpia.searchParams.delete('imagenes');
        window.history.replaceState({}, '', limpia.pathname + limpia.search + '#imagenes');
    }

    actualizarSeleccion();
})();
