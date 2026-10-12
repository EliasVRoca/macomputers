@extends('layouts.app')

@section('titulo', 'Inicio')
@section('encabezado', 'Inicio')
@section('subtitulo', 'Su rol no tiene módulos asignados. Consulte con el gerente.')

@section('contenido')
    <div class="card text-sm text-ink-muted">
        Cuenta: <span class="font-medium text-ink">{{ auth()->user()->username }}</span>
    </div>
@endsection
