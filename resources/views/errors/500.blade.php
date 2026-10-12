@extends(auth()->check() ? 'layouts.app' : 'layouts.blank')

@section('titulo', 'Error del servidor')
@section('encabezado', 'Error del servidor')

@section('contenido')
    <x-error-page
        codigo="500"
        titulo="Algo salió mal"
        mensaje="Ocurrió un error inesperado. Intente nuevamente; si el problema continúa, avise al encargado del sistema." />
@endsection
