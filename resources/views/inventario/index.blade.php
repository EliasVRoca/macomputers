@extends('layouts.app')

@section('titulo', 'Inventario')
@section('encabezado', 'Inventario')
@section('subtitulo', 'Consulta cuántas unidades hay de cada producto en cada sucursal y registra entradas, salidas o ajustes.')
@section('acciones')
    <button type="button" class="btn btn-primary" data-modal-abrir="modal-movimiento">
        <x-icon name="plus" class="size-4" /> Registrar movimiento
    </button>
@endsection

@section('contenido')
@php
    $paramsTabs = collect(request()->query())->except(['page', 'existencias_page', 'kardex_page'])->all();
    $tabs = ['existencias' => 'Existencias', 'historial' => 'Historial de movimientos'];
@endphp

@include('inventario._kpis')

<nav class="tabs mb-5" aria-label="Secciones del inventario">
    @foreach ($tabs as $clave => $etiqueta)
        <a href="{{ route('inventario.index', array_merge($paramsTabs, ['tab' => $clave])) }}"
           class="tab {{ $tab === $clave ? 'activa' : '' }}"
           @if ($tab === $clave) aria-current="page" @endif>
            {{ $etiqueta }}
        </a>
    @endforeach
</nav>

@if ($tab === 'historial')
    @include('inventario._tab-historial')
@else
    @include('inventario._tab-existencias')
@endif

@include('inventario._modal-movimiento')

@if ($puedeTraspasar)
    @include('inventario._modal-traspaso')
@endif

<script id="stock-datos" type="application/json">{!! json_encode($stockMapa, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}</script>
<script id="sucursales-datos" type="application/json">{!! json_encode($sucursales->pluck('nombre', 'id')->all(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}</script>
@endsection
