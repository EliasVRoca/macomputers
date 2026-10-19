@extends('layouts.app')

@section('titulo', 'Disponibilidad')
@section('encabezado', 'Disponibilidad de productos')
@section('subtitulo', 'Existencias en tiempo real por sucursal, sin llamar a la otra tienda.')
@section('acciones')
    <a href="{{ route('disponibilidad.export', request()->query()) }}" class="btn btn-secondary">
        <x-icon name="download" class="size-4" /> Exportar CSV
    </a>
@endsection

@section('contenido')
    @include('disponibilidad._filtros')
    @include('disponibilidad._tabla')
@endsection
