@extends(auth()->check() ? 'layouts.app' : 'layouts.blank')

@section('titulo', 'Acceso denegado')
@section('encabezado', 'Acceso denegado')

@section('contenido')
    <x-error-page
        codigo="403"
        titulo="No tiene permiso para acceder"
        :mensaje="$exception?->getMessage() ?: 'Su rol no tiene acceso a este módulo. Si cree que es un error, consulte con el gerente.'" />
@endsection
