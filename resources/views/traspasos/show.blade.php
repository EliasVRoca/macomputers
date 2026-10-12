@extends('layouts.app')

@section('titulo', 'Traspaso #'.$traspaso->id)
@section('encabezado', 'Traspaso #'.$traspaso->id)
@section('subtitulo', $traspaso->fecha->format('d/m/Y').' · '.$traspaso->origen?->nombre.' → '.$traspaso->destino?->nombre)
@section('acciones')
    <a href="{{ route('traspasos.index') }}" class="btn btn-secondary">Volver</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Producto</th><th>Código</th><th class="text-right">Cantidad</th></tr></thead>
        <tbody>
        @foreach ($traspaso->items as $item)
            <tr>
                <td>{{ $item->producto?->nombre }}</td>
                <td class="font-mono text-xs">{{ $item->producto?->codigo }}</td>
                <td class="text-right">{{ $item->cantidad }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<p class="mt-3 text-sm text-ink-muted">Registrado por {{ $traspaso->usuario?->username }}.</p>
@endsection
