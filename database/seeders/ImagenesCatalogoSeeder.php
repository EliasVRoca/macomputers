<?php

namespace Database\Seeders;

use App\Exceptions\BusquedaImagenException;
use App\Models\Producto;
use App\Services\Imagenes\BuscadorImagenes;
use App\Services\Imagenes\DescargadorImagen;
use App\Services\Productos\ProductoImagenService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Descarga una imagen principal para cada producto del catálogo usando
 * Wikimedia Commons (fotos reales con licencia y atribución).
 *
 * Es idempotente: omite los productos que ya tienen imágenes.
 *
 * Ejecución:
 *   php artisan db:seed --class=ImagenesCatalogoSeeder
 */
class ImagenesCatalogoSeeder extends Seeder
{
    private BuscadorImagenes $buscador;

    private DescargadorImagen $descargador;

    private ProductoImagenService $imagenes;

    /** @var array<string, array<string, mixed>> */
    private array $fichas = [];

    public function __construct()
    {
        $this->buscador = app(BuscadorImagenes::class);
        $this->descargador = app(DescargadorImagen::class);
        $this->imagenes = app(ProductoImagenService::class);
    }

    public function run(): void
    {
        $datos = require database_path('seeders/data/catalogo.php');

        foreach ($datos['productos'] as $fila) {
            $this->fichas[$fila['codigo']] = $fila;
        }

        $productos = Producto::query()
            ->where('activo', true)
            ->doesntHave('imagenes')
            ->orderBy('id')
            ->when((int) env('CATALOGO_IMAGENES_LIMITE', 0) > 0, fn ($query) => $query->limit((int) env('CATALOGO_IMAGENES_LIMITE')))
            ->get();

        if ($productos->isEmpty()) {
            $this->command?->info('Todos los productos ya tienen imagen.');

            return;
        }

        $this->command?->info("Buscando imágenes para {$productos->count()} productos...");

        $ok = 0;
        $fallidos = 0;
        $total = $productos->count();

        foreach ($productos as $indice => $producto) {
            $numero = $indice + 1;
            $ficha = $this->fichas[$producto->codigo] ?? [];

            $this->command?->line("[{$numero}/{$total}] {$producto->nombre}");

            $resultado = $this->resolverImagen($producto, $ficha);

            if ($resultado === null) {
                $fallidos++;
                $this->command?->warn('  Sin imagen disponible.');

                continue;
            }

            try {
                $archivo = $this->descargarImagen($resultado);
            } catch (BusquedaImagenException $e) {
                $fallidos++;
                $this->command?->warn('  No se pudo descargar: '.$e->getMessage());

                continue;
            }

            $this->imagenes->registrar($producto, $archivo['ruta'], [
                'proveedor' => $resultado['proveedor'] ?? 'wikimedia',
                'origen_url' => $resultado['origen_url'] ?? null,
                'titulo' => $resultado['titulo'] ?? null,
                'autor' => $resultado['autor'] ?? null,
                'licencia' => $resultado['licencia'] ?? null,
                'licencia_url' => $resultado['licencia_url'] ?? null,
                'es_principal' => true,
            ]);

            $ok++;
            $this->command?->info('  Imagen guardada: '.$archivo['ruta']);

            // La API de Wikimedia recomienda no saturar con peticiones seguidas.
            usleep(900_000);
        }

        $sinImagen = Producto::query()
            ->where('activo', true)
            ->doesntHave('imagenes')
            ->count();

        $this->command?->info("Imágenes cargadas: {$ok} · Fallidas: {$fallidos} · Productos sin imagen: {$sinImagen}");
    }

    /**
     * @param  array<string, mixed>  $ficha
     * @return array<string, mixed>|null
     */
    private function resolverImagen(Producto $producto, array $ficha): ?array
    {
        $consulta = trim((string) ($ficha['img'] ?? $producto->nombre));

        $resultados = $this->buscarConReintentos($consulta);
        $elegido = $this->elegir($resultados, $consulta);

        if ($elegido !== null) {
            return $elegido;
        }

        // Segundo intento: una consulta más amplia por marca y tipo de producto.
        $fallback = $this->consultaFallback($producto);
        $consultaAmplia = $this->conMarca($producto->marca, $fallback);

        $this->command?->line("  Sin coincidencia exacta; probando: {$consultaAmplia}");
        $resultados = $this->buscarConReintentos($consultaAmplia);
        $elegido = $this->elegir($resultados, $consultaAmplia);

        if ($elegido !== null) {
            return $elegido;
        }

        // Último intento: la consulta genérica de la categoría.
        $resultados = $this->buscarConReintentos($fallback);
        $elegido = $this->elegir($resultados, $fallback);

        if ($elegido !== null) {
            $this->command?->line("  Usando imagen representativa de la categoría: {$fallback}");
        }

        return $elegido;
    }

    private function conMarca(string $marca, string $consulta): string
    {
        $marca = trim($marca);
        $consulta = trim($consulta);

        if ($marca === '' || Str::contains($this->normalizar($consulta), $this->normalizar($marca))) {
            return $consulta;
        }

        if (in_array($this->normalizar($marca), ['generica', 'ma computers', 'm&a computers'], true)
            || str_contains($this->normalizar($marca), 'm&a')) {
            return $consulta;
        }

        return $marca.' '.$consulta;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buscarConReintentos(string $consulta, int $intentos = 3): array
    {
        for ($i = 1; $i <= $intentos; $i++) {
            try {
                $respuesta = $this->buscador->buscar($consulta);

                return is_array($respuesta['resultados'] ?? null) ? $respuesta['resultados'] : [];
            } catch (BusquedaImagenException $e) {
                Log::warning('Búsqueda de imagen fallida.', [
                    'consulta' => $consulta,
                    'intento' => $i,
                    'status' => $e->status,
                    'error' => $e->getMessage(),
                ]);

                if ($e->status < 500 || $i === $intentos) {
                    $this->command?->warn('  Búsqueda sin respuesta: '.$e->getMessage());

                    return [];
                }

                sleep(8 * $i);
            } catch (\Throwable $e) {
                Log::warning('Error inesperado al buscar imágenes.', [
                    'consulta' => $consulta,
                    'error' => $e->getMessage(),
                ]);

                if ($i === $intentos) {
                    $this->command?->warn('  Error inesperado: '.$e->getMessage());

                    return [];
                }

                sleep(5 * $i);
            }
        }

        return [];
    }

    /**
     * Elige el resultado con mejor puntuación según marca/modelo, dimensiones y tipo de foto.
     *
     * @param  array<int, array<string, mixed>>  $resultados
     * @return array<string, mixed>|null
     */
    private function elegir(array $resultados, string $consulta): ?array
    {
        $tokens = $this->tokens($consulta);
        $consultaNormalizada = $this->normalizar($consulta);
        $candidatos = [];

        foreach ($resultados as $resultado) {
            $titulo = $this->normalizar((string) ($resultado['titulo'] ?? ''));

            if ($titulo === '' || $this->descartar($titulo)) {
                continue;
            }

            // Evita que una búsqueda de laptop termine mostrando un teclado,
            // una placa o unos auriculares solo porque la foto es vistosa.
            $ajenas = [
                'keyboard' => ['keyboard', 'teclado', 'combo'],
                'motherboard' => ['motherboard', 'placa'],
                'headset' => ['headset', 'audifono', 'auricular'],
                'webcam' => ['webcam', 'camara'],
            ];

            $descartarPorAjena = false;

            foreach ($ajenas as $palabra => $permitidas) {
                if (! str_contains($titulo, $palabra)) {
                    continue;
                }

                $permitida = false;

                foreach ($permitidas as $clave) {
                    if (str_contains($consultaNormalizada, $clave)) {
                        $permitida = true;
                        break;
                    }
                }

                if (! $permitida) {
                    $descartarPorAjena = true;
                    break;
                }
            }

            if ($descartarPorAjena) {
                continue;
            }

            $ancho = (int) ($resultado['ancho'] ?? 0);
            $alto = (int) ($resultado['alto'] ?? 0);

            if ($ancho > 0 && $alto > 0 && ($ancho < 400 || $alto < 250)) {
                continue;
            }

            $puntaje = 0;
            $coincidencias = [];

            foreach ($tokens as $token) {
                if (str_contains($titulo, $token)) {
                    $puntaje += 8;
                    $coincidencias[] = $token;
                }
            }

            // Si la consulta incluye un modelo (E14, RTX 4060, B650M...), exigimos
            // que al menos uno de esos tokens aparezca en el título. Así se evitan
            // adaptadores, capturas de video y fotos de otro modelo.
            $requeridos = array_values(array_filter(
                $tokens,
                fn (string $token): bool => strlen($token) >= 3 && preg_match('/\d/', $token) === 1,
            ));

            if ($requeridos !== [] && array_intersect($requeridos, $coincidencias) === []) {
                continue;
            }

            if ($ancho >= 1000) {
                $puntaje += 4;
            }

            if ($ancho >= $alto) {
                $puntaje += 2;
            } else {
                $puntaje -= 4;
            }

            if (preg_match('/\b(photo|product|front|top|white background)\b/', $titulo) === 1) {
                $puntaje += 3;
            }

            $candidatos[] = ['resultado' => $resultado, 'puntaje' => $puntaje];
        }

        usort($candidatos, fn (array $a, array $b): int => $b['puntaje'] <=> $a['puntaje']);

        return $candidatos[0]['resultado'] ?? null;
    }

    /**
     * Descarta empaques, logos, capturas y material gráfico que no sirve como foto de producto.
     */
    private function descartar(string $titulo): bool
    {
        $palabras = [
            'logo', 'icon', 'box', 'packaging', 'verpackung', 'screenshot', 'diagram',
            'schematic', 'drawing', 'blueprint', 'benchmark', 'chart', 'graph', 'video',
            'youtube', 'teardown', 'disassembly', 'label', 'sticker', 'poster',
            'advertisement', 'wallpaper', 'artwork', 'render', 'unboxing', 'badge',
            'comparison', 'microscope', 'silicon', 'closed', 'схема', 'тест',
        ];

        if (preg_match('/\d{2}:\d{2}:\d{2}/', $titulo) === 1) {
            return true;
        }

        foreach ($palabras as $palabra) {
            if (preg_match('/\b'.preg_quote($palabra, '/').'\b/', $titulo) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $texto): array
    {
        $texto = $this->normalizar($texto);
        $tokens = preg_split('/[^a-z0-9]+/', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $ruido = [
            'de', 'la', 'el', 'los', 'las', 'para', 'con', 'sin', 'the', 'and', 'for',
            'with', 'computer', 'laptop', 'pc', 'gaming', 'graphics', 'card',
            'motherboard', 'module', 'unit', 'series',
        ];

        return array_values(array_unique(array_filter(
            $tokens,
            fn (string $token): bool => strlen($token) >= 2 && ! in_array($token, $ruido, true),
        )));
    }

    private function normalizar(string $texto): string
    {
        return mb_strtolower(Str::ascii($texto));
    }

    /**
     * @param  array<string, mixed>  $resultado
     * @return array{ruta: string, ancho: int, alto: int}
     */
    private function descargarImagen(array $resultado): array
    {
        $miniatura = (string) ($resultado['miniatura'] ?? '');
        $original = (string) ($resultado['url'] ?? '');
        $origen = $resultado['origen_url'] ?? null;

        $grande = $this->ampliarMiniatura($miniatura);

        try {
            return $this->descargador->descargar($grande !== '' ? $grande : $original, $original, $origen);
        } catch (BusquedaImagenException $e) {
            // Si la miniatura grande falla, se intenta con la miniatura original de 500px.
            if ($miniatura !== '' && $miniatura !== $grande) {
                return $this->descargador->descargar($miniatura, null, $origen);
            }

            throw $e;
        }
    }

    private function ampliarMiniatura(string $url): string
    {
        if ($url === '') {
            return '';
        }

        return (string) preg_replace_callback(
            '/(\/)(\d+)px-/',
            fn (array $coincidencia): string => $coincidencia[1].'1280px-',
            $url,
            1,
        );
    }

    private function consultaFallback(Producto $producto): string
    {
        $nombre = $this->normalizar($producto->nombre);
        $categoria = $producto->categoria?->nombre ?? '';

        if ($categoria === 'Procesadores') {
            return $this->normalizar($producto->marca) === 'intel'
                ? 'Intel Core processor'
                : 'AMD Ryzen processor';
        }

        // Primero se busca una consulta específica según el tipo de producto.
        $porNombre = [
            'pasta termica' => 'thermal paste',
            'base refrigerante' => 'laptop cooling pad',
            'refrigeracion liquida' => 'AIO liquid cooler',
            'cooler interno' => 'laptop cooling fan',
            'ventilador' => 'case fans',
            'cooler' => 'CPU cooler',
            'fuente' => 'ATX power supply',
            'gabinete' => 'ATX computer case',
            'mousepad' => 'mousepad',
            'combo teclado' => 'keyboard mouse combo',
            'teclado para laptop' => 'laptop keyboard replacement',
            'teclado' => 'mechanical keyboard',
            'mouse' => 'Logitech mouse',
            'audifonos' => 'gaming headset',
            'parlantes' => 'computer speakers',
            'modulo de camara' => 'laptop webcam module',
            'camara web' => 'USB webcam',
            'webcam' => 'USB webcam',
            'control' => 'game controller',
            'hub usb' => 'USB hub',
            'dock' => 'USB-C docking station',
            'hdmi' => 'HDMI cable',
            'displayport' => 'DisplayPort cable',
            'cargador universal' => 'universal laptop charger',
            'cargador' => 'USB-C charger',
            'ups' => 'UPS uninterruptible power supply',
            'regleta' => 'power strip surge protector',
            'adaptador sata' => 'SATA to USB adapter',
            'splitter' => 'fan splitter cable',
            'organizador' => 'cable organizer',
            'pantalla' => 'laptop LCD panel',
            'bateria' => 'laptop battery pack',
            'bisagra' => 'laptop hinge',
            'dc jack' => 'laptop DC jack',
            'touchpad' => 'laptop touchpad',
            'escaner' => 'flatbed scanner',
            'toner' => 'toner cartridge',
            'tinta' => 'ink bottle',
            'impresora' => 'printer',
            'monitor portatil' => 'portable monitor',
            'monitor curvado' => 'curved gaming monitor',
            'monitor' => 'computer monitor',
            'mesh' => 'mesh Wi-Fi',
            'router' => 'Wi-Fi router',
            'switch' => 'network switch',
            'access point' => 'access point',
            'plc' => 'powerline adapter',
            'cable utp' => 'Cat6 network cable',
            'patch panel' => 'patch panel',
        ];

        foreach ($porNombre as $clave => $consulta) {
            if (str_contains($nombre, $clave)) {
                return $consulta;
            }
        }

        return match ($producto->categoria?->nombre ?? '') {
            'Laptops' => 'laptop',
            'Computadoras de escritorio' => 'desktop computer tower',
            'Procesadores' => 'CPU processor',
            'Tarjetas madre' => 'computer motherboard',
            'Tarjetas de video' => 'graphics card',
            'Memorias RAM' => 'RAM',
            'Almacenamiento' => 'SSD',
            'Fuentes de poder' => 'ATX power supply',
            'Gabinetes' => 'ATX computer case',
            'Refrigeración' => 'CPU cooler',
            'Monitores' => 'computer monitor',
            'Periféricos' => 'computer peripheral',
            'Redes y conectividad' => 'Wi-Fi router',
            'Impresoras y consumibles' => 'printer',
            'Accesorios y cables' => 'USB hub',
            'Repuestos para laptops' => 'laptop parts',
            default => 'computer hardware',
        };
    }
}
