<?php

namespace App\Services;

use App\Enums\CondicionProducto;
use App\Enums\ResultadoBusqueda;
use App\Models\BusquedaProducto;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Buscador inteligente (RF20 · CU19).
 *
 * Busca por texto en nombre, código, marca, modelo y atributos; calcula stock
 * por sucursal, sugiere similares y registra las búsquedas para el reporte
 * "lo más buscado que no tenemos".
 */
class BuscadorService
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function buscar(array $filtros, User $usuario): array
    {
        $termino = $this->normalizar((string) ($filtros['termino'] ?? ''));
        $tokens = $this->tokens($termino);
        $sucursales = Sucursal::where('activa', true)->orderBy('nombre')->get();
        $sucursalId = $this->sucursalContexto($filtros, $usuario);

        if ($tokens === []) {
            return [
                'termino' => $termino,
                'resultado' => ResultadoBusqueda::NoExiste->value,
                'total' => 0,
                'productos' => [],
                'similares' => [],
                'sucursales' => $this->sucursalesData($sucursales),
            ];
        }

        $productos = $this->consulta($tokens, $filtros, $sucursalId)->get();
        $resultado = $this->resultado($productos, $sucursales);

        $this->registrar($termino, $usuario, $resultado, $productos->first()?->id, $sucursalId);

        $similares = $productos->isEmpty()
            ? $this->similares($tokens, $filtros)
            : collect();

        return [
            'termino' => $termino,
            'resultado' => $resultado->value,
            'total' => $productos->count(),
            'productos' => $productos
                ->map(fn (Producto $producto): array => $this->productoData($producto, $sucursales, $sucursalId))
                ->values()
                ->all(),
            'similares' => $similares
                ->map(fn (Producto $producto): array => $this->productoData($producto, $sucursales, $sucursalId))
                ->values()
                ->all(),
            'sucursales' => $this->sucursalesData($sucursales),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array{items: LengthAwarePaginator, total: int, terminos: int, top: ?string}
     */
    public function reporte(array $filtros): array
    {
        $base = $this->consultaReporte($filtros);

        $items = (clone $base)
            ->selectRaw(
                'termino, count(*) as veces, sum(resultado = ?) as no_existe, sum(resultado = ?) as sin_stock, max(created_at) as ultima',
                [ResultadoBusqueda::NoExiste->value, ResultadoBusqueda::SinStock->value],
            )
            ->groupBy('termino')
            ->orderByDesc('veces')
            ->orderBy('termino')
            ->paginate(15)
            ->withQueryString();

        return [
            'items' => $items,
            'total' => (clone $base)->count(),
            'terminos' => (clone $base)->distinct()->count('termino'),
            'top' => (clone $base)
                ->select('termino')
                ->groupBy('termino')
                ->orderByRaw('count(*) desc')
                ->value('termino'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return Collection<int, object>
     */
    public function exportarReporte(array $filtros): Collection
    {
        return $this->consultaReporte($filtros)
            ->selectRaw(
                'termino, count(*) as veces, sum(resultado = ?) as no_existe, sum(resultado = ?) as sin_stock, max(created_at) as ultima',
                [ResultadoBusqueda::NoExiste->value, ResultadoBusqueda::SinStock->value],
            )
            ->groupBy('termino')
            ->orderByDesc('veces')
            ->orderBy('termino')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consultaReporte(array $filtros): Builder
    {
        return BusquedaProducto::query()
            ->whereIn('resultado', [ResultadoBusqueda::SinStock, ResultadoBusqueda::NoExiste])
            ->when($filtros['resultado'] ?? null, fn (Builder $q) => $q->where('resultado', $filtros['resultado']))
            ->when($filtros['sucursal_id'] ?? null, fn (Builder $q) => $q->where('sucursal_id', (int) $filtros['sucursal_id']))
            ->when($filtros['desde'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '>=', $filtros['desde']))
            ->when($filtros['hasta'] ?? null, fn (Builder $q) => $q->whereDate('created_at', '<=', $filtros['hasta']));
    }

    /**
     * Búsqueda base por tokens (cada token debe coincidir en algún campo).
     *
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $tokens, array $filtros, ?int $sucursalId): Builder
    {
        $marca = $this->normalizar((string) ($filtros['marca'] ?? ''));

        return Producto::query()
            ->where('activo', true)
            ->with(['categoria', 'inventarios', 'imagenes', 'atributos'])
            ->where(fn (Builder $query) => $this->aplicarTokens($query, $tokens))
            ->when($filtros['categoria_id'] ?? null, fn (Builder $q) => $q->where('categoria_id', (int) $filtros['categoria_id']))
            ->when($marca !== '', fn (Builder $q) => $q->where('marca', 'like', '%'.$marca.'%'))
            ->when($filtros['condicion'] ?? null, fn (Builder $q) => $q->where('condicion', $filtros['condicion']))
            ->when(($filtros['solo_stock'] ?? false) && $sucursalId !== null, fn (Builder $q) => $q->whereHas('inventarios', fn (Builder $i) => $i
                ->where('sucursal_id', $sucursalId)
                ->where('cantidad', '>', 0)))
            ->when(($filtros['solo_stock'] ?? false) && $sucursalId === null, fn (Builder $q) => $q->whereHas('inventarios', fn (Builder $i) => $i->where('cantidad', '>', 0)))
            ->orderByRaw('CASE WHEN LOWER(codigo) = ? THEN 0 ELSE 1 END', [$this->normalizar((string) ($filtros['termino'] ?? ''))])
            ->orderBy('nombre')
            ->limit(24);
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function aplicarTokens(Builder $query, array $tokens): void
    {
        foreach ($tokens as $token) {
            $like = '%'.$token.'%';

            $query->where(function (Builder $q) use ($like): void {
                $q->where('nombre', 'like', $like)
                    ->orWhere('codigo', 'like', $like)
                    ->orWhere('marca', 'like', $like)
                    ->orWhere('modelo', 'like', $like)
                    ->orWhereHas('atributos', fn (Builder $a) => $a
                        ->where('atributo', 'like', $like)
                        ->orWhere('valor', 'like', $like));
            });
        }
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function aplicarAlgunToken(Builder $query, array $tokens): void
    {
        foreach ($tokens as $i => $token) {
            $like = '%'.$token.'%';
            $metodo = $i === 0 ? 'where' : 'orWhere';

            $query->{$metodo}(function (Builder $q) use ($like): void {
                $q->where('nombre', 'like', $like)
                    ->orWhere('codigo', 'like', $like)
                    ->orWhere('marca', 'like', $like)
                    ->orWhere('modelo', 'like', $like)
                    ->orWhereHas('atributos', fn (Builder $a) => $a
                        ->where('atributo', 'like', $like)
                        ->orWhere('valor', 'like', $like));
            });
        }
    }

    /**
     * Similares: misma categoría y atributos compartidos, marca distinta.
     *
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $filtros
     * @return Collection<int, Producto>
     */
    private function similares(array $tokens, array $filtros): Collection
    {
        $candidatos = Producto::query()
            ->where('activo', true)
            ->with(['categoria', 'inventarios', 'imagenes', 'atributos'])
            ->where(fn (Builder $query) => $this->aplicarAlgunToken($query, $tokens))
            ->when($filtros['categoria_id'] ?? null, fn (Builder $q) => $q->where('categoria_id', (int) $filtros['categoria_id']))
            ->when($filtros['condicion'] ?? null, fn (Builder $q) => $q->where('condicion', $filtros['condicion']))
            ->limit(40)
            ->get();

        if ($candidatos->isEmpty()) {
            return collect();
        }

        $referencia = $candidatos
            ->sortByDesc(fn (Producto $producto): int => $this->puntajeReferencia($producto, $tokens))
            ->first();
        $atributosReferencia = $referencia->atributos
            ->mapWithKeys(fn ($atributo): array => [$atributo->atributo => $atributo->valor]);

        return $candidatos
            ->filter(fn (Producto $producto): bool => $producto->id !== $referencia->id)
            ->filter(fn (Producto $producto): bool => $producto->categoria_id === $referencia->categoria_id)
            ->filter(fn (Producto $producto): bool => $producto->marca !== $referencia->marca)
            ->map(function (Producto $producto) use ($atributosReferencia, $tokens): array {
                $atributos = $producto->atributos
                    ->mapWithKeys(fn ($atributo): array => [$atributo->atributo => $atributo->valor]);
                $score = 0;

                foreach ($atributosReferencia as $clave => $valor) {
                    if (($atributos[$clave] ?? null) === $valor) {
                        $score += 3;
                    } elseif (isset($atributos[$clave])) {
                        $score += 1;
                    }
                }

                foreach ($tokens as $token) {
                    if (mb_stripos($producto->nombre, $token) !== false) {
                        $score += 2;
                    }
                }

                return ['producto' => $producto, 'score' => $score];
            })
            ->filter(fn (array $item): bool => $item['score'] > 0)
            ->sortByDesc('score')
            ->take(6)
            ->pluck('producto')
            ->values();
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function puntajeReferencia(Producto $producto, array $tokens): int
    {
        $puntaje = $producto->atributos->count();

        foreach ($tokens as $token) {
            if (mb_stripos($producto->nombre, $token) !== false) {
                $puntaje += 3;
            }
        }

        return $puntaje;
    }

    /**
     * @param  Collection<int, Producto>  $productos
     * @param  Collection<int, Sucursal>  $sucursales
     */
    private function resultado(Collection $productos, Collection $sucursales): ResultadoBusqueda
    {
        if ($productos->isEmpty()) {
            return ResultadoBusqueda::NoExiste;
        }

        $idsActivos = $sucursales->pluck('id');
        $hayStock = $productos->contains(fn (Producto $producto): bool => $producto->inventarios
            ->whereIn('sucursal_id', $idsActivos)
            ->sum('cantidad') > 0);

        return $hayStock ? ResultadoBusqueda::Encontrado : ResultadoBusqueda::SinStock;
    }

    /**
     * @param  Collection<int, Sucursal>  $sucursales
     * @return array<string, mixed>
     */
    private function productoData(Producto $producto, Collection $sucursales, ?int $sucursalId): array
    {
        $idsActivos = $sucursales->pluck('id');
        $stock = $producto->inventarios
            ->whereIn('sucursal_id', $idsActivos)
            ->mapWithKeys(fn ($inventario): array => [(string) $inventario->sucursal_id => (int) $inventario->cantidad])
            ->all();

        $total = array_sum($stock);
        $local = $sucursalId !== null ? (int) ($stock[(string) $sucursalId] ?? 0) : $total;
        $imagen = $producto->imagenes->firstWhere('es_principal', true) ?? $producto->imagenes->first();
        $condicion = $producto->condicion ?? CondicionProducto::Nuevo;

        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo,
            'marca' => $producto->marca,
            'modelo' => $producto->modelo,
            'categoria' => $producto->categoria?->nombre,
            'condicion' => $condicion->value,
            'condicion_etiqueta' => $condicion->etiqueta(),
            'precio_venta' => (float) $producto->precio_venta,
            'stock' => $stock,
            'total' => $total,
            'local' => $local,
            'stock_minimo' => (int) $producto->stock_minimo,
            'bajo_minimo' => $total <= (int) $producto->stock_minimo,
            'imagen' => $imagen?->src(),
            'atributos' => $producto->atributos
                ->mapWithKeys(fn ($atributo): array => [$atributo->atributo => $atributo->valor])
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Sucursal>  $sucursales
     * @return array<int, array{id: int, nombre: string}>
     */
    private function sucursalesData(Collection $sucursales): array
    {
        return $sucursales
            ->map(fn (Sucursal $sucursal): array => ['id' => $sucursal->id, 'nombre' => $sucursal->nombre])
            ->values()
            ->all();
    }

    /**
     * Sucursal de contexto: gerente puede elegir; el resto queda en la suya.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function sucursalContexto(array $filtros, User $usuario): ?int
    {
        if ($usuario->esGerente() && ! empty($filtros['sucursal_id'])) {
            return (int) $filtros['sucursal_id'];
        }

        return $usuario->sucursal_id !== null ? (int) $usuario->sucursal_id : null;
    }

    private function registrar(string $termino, User $usuario, ResultadoBusqueda $resultado, ?int $productoId, ?int $sucursalId): void
    {
        if (mb_strlen($termino) < 2) {
            return;
        }

        $duplicada = BusquedaProducto::query()
            ->where('user_id', $usuario->id)
            ->where('termino', $termino)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->exists();

        if ($duplicada) {
            return;
        }

        BusquedaProducto::create([
            'termino' => $termino,
            'sucursal_id' => $sucursalId ?? $usuario->sucursal_id,
            'user_id' => $usuario->id,
            'resultado' => $resultado,
            'producto_id' => $productoId,
        ]);
    }

    private function normalizar(string $valor): string
    {
        $texto = strip_tags(trim($valor));
        $texto = preg_replace('/[\x00-\x1F\x7F]/u', '', $texto) ?? '';
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';

        return mb_strtolower(trim($texto));
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $termino): array
    {
        if ($termino === '') {
            return [];
        }

        return collect(preg_split('/\s+/u', $termino) ?: [])
            ->filter(fn (string $token): bool => mb_strlen($token) >= 2)
            ->map(fn (string $token): string => $token)
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }
}
