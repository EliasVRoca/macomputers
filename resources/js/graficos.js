// Gráficos con Chart.js: cada <canvas data-grafico="{json}"> se convierte en un
// gráfico. La librería se importa de forma dinámica para no cargarla en páginas
// sin gráficos. Los colores se leen de las variables CSS y se actualizan al
// cambiar el tema (claro/oscuro).

const graficos = [];

const css = (variable, defecto) => {
    const valor = getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
    return valor || defecto;
};

const coloresTema = () => ({
    texto: css('--color-ink-subtle', '#7b7b78'),
    rejilla: css('--color-hairline', '#d3cec6'),
    marca: css('--color-brand', '#0007cb'),
    superficie: css('--color-surface-1', '#ffffff'),
});

const PALETA = ['#4f6df6', '#0a7a2e', '#b45309', '#c41c1c', '#8b5cf6', '#0e7490', '#d97706', '#64748b'];

const numero = (valor) => Number(valor) || 0;

const formatear = (valor, formato) => {
    if (valor == null || Number.isNaN(valor)) {
        valor = 0;
    }

    if (formato === 'monto') {
        return 'Bs ' + valor.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    return valor.toLocaleString('es-BO');
};

const compacto = (valor, formato) => {
    if (valor == null || Number.isNaN(valor)) {
        return '';
    }

    if (formato === 'monto') {
        const abs = Math.abs(valor);

        return abs >= 1000
            ? 'Bs ' + Intl.NumberFormat('es-BO', { notation: 'compact', maximumFractionDigits: 1 }).format(valor)
            : 'Bs ' + valor.toLocaleString('es-BO');
    }

    return Intl.NumberFormat('es-BO', { notation: 'compact', maximumFractionDigits: 1 }).format(valor);
};

const sinDatos = (config) => {
    if (!config.etiquetas || config.etiquetas.length === 0) {
        return true;
    }

    if (config.tipo === 'dona') {
        const total = (config.series ?? []).reduce(
            (suma, serie) => suma + (serie.datos ?? []).reduce((a, b) => a + numero(b), 0),
            0,
        );

        return total <= 0;
    }

    return false;
};

const colorSerie = (indice) => (indice === 0 ? coloresTema().marca : PALETA[(indice - 1) % PALETA.length]);

const etiquetaTooltip = (ctx, config, horizontal) => {
    const valor = horizontal ? ctx.parsed.x : (ctx.parsed.y ?? ctx.parsed);
    return ` ${ctx.dataset.label ?? ''}: ${formatear(valor, ctx.dataset.formatoSerie ?? config.formato)}`;
};

const construir = (lienzo, config, Chart) => {
    const tema = coloresTema();

    if (config.tipo === 'dona') {
        return new Chart(lienzo, {
            type: 'doughnut',
            data: {
                labels: config.etiquetas,
                datasets: [{
                    data: (config.series[0]?.datos ?? []).map(numero),
                    backgroundColor: config.etiquetas.map((_, indice) => PALETA[indice % PALETA.length]),
                    borderColor: tema.superficie,
                    borderWidth: 2,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: { color: tema.texto, boxWidth: 8, boxHeight: 8, usePointStyle: true },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${formatear(ctx.parsed, config.formato)}`,
                        },
                    },
                },
            },
        });
    }

    const linea = config.tipo === 'linea';
    const horizontal = config.tipo === 'barrasH';
    const usaDual = (config.series ?? []).some((serie) => serie.eje === 'derecho');
    const series = (config.series ?? []).map((serie, indice) => {
        const color = serie.color ?? colorSerie(indice);

        return {
            label: serie.nombre,
            data: (serie.datos ?? []).map(numero),
            backgroundColor: color,
            borderColor: color,
            borderWidth: 1.5,
            tension: linea ? 0.35 : undefined,
            pointRadius: linea && config.etiquetas.length <= 60 ? 2 : 0,
            pointHoverRadius: 4,
            ...(usaDual ? { yAxisID: serie.eje === 'derecho' ? 'y1' : 'y' } : {}),
            formatoSerie: serie.formato ?? config.formato,
        };
    });

    const escalas = {
        x: {
            ticks: {
                color: tema.texto,
                maxTicksLimit: 12,
                maxRotation: 0,
                callback: horizontal ? (valor) => compacto(valor, config.formato) : undefined,
            },
            grid: { color: tema.rejilla, drawBorder: false },
        },
        y: {
            beginAtZero: true,
            ticks: {
                color: tema.texto,
                callback: horizontal ? undefined : (valor) => compacto(valor, config.formato),
            },
            grid: { color: tema.rejilla, drawBorder: false },
        },
    };

    if (usaDual) {
        escalas.y1 = {
            beginAtZero: true,
            position: 'right',
            ticks: {
                color: tema.texto,
                callback: (valor) => compacto(valor, 'monto'),
            },
            grid: { drawOnChartArea: false },
        };
    }

    return new Chart(lienzo, {
        type: linea ? 'line' : 'bar',
        data: { labels: config.etiquetas, datasets: series },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: horizontal ? 'y' : 'x',
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: series.length > 1,
                    labels: { color: tema.texto, boxWidth: 8, boxHeight: 8, usePointStyle: true },
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => etiquetaTooltip(ctx, config, horizontal),
                    },
                },
            },
            scales: escalas,
        },
    });
};

const actualizarTema = () => {
    const tema = coloresTema();

    graficos.forEach((grafico) => {
        const opciones = grafico.options ?? {};

        if (opciones.scales) {
            Object.values(opciones.scales).forEach((escala) => {
                if (escala.ticks) {
                    escala.ticks.color = tema.texto;
                }

                if (escala.grid) {
                    escala.grid.color = tema.rejilla;
                }
            });
        }

        if (opciones.plugins?.legend) {
            opciones.plugins.legend.labels.color = tema.texto;
        }

        grafico.update();
    });
};

export async function iniciarGraficos() {
    const lienzos = [...document.querySelectorAll('canvas[data-grafico]')];

    if (lienzos.length === 0) {
        return;
    }

    const { Chart } = await import('chart.js/auto');

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

    lienzos.forEach((lienzo) => {
        let config;

        try {
            config = JSON.parse(lienzo.dataset.grafico);
        } catch {
            return;
        }

        const vacio = lienzo.closest('[data-grafico-tarjeta]')?.querySelector('[data-grafico-vacio]');

        if (sinDatos(config)) {
            lienzo.hidden = true;

            if (vacio) {
                vacio.hidden = false;
            }

            return;
        }

        try {
            graficos.push(construir(lienzo, config, Chart));
        } catch (error) {
            console.error('No se pudo dibujar el gráfico', config.tipo, error);
        }
    });

    new MutationObserver(actualizarTema).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}
