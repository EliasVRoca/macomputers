@extends(auth()->check() ? 'layouts.app' : 'layouts.blank')

@section('titulo', 'Sesión expirada')
@section('encabezado', 'Sesión expirada')

@section('contenido')
    <x-error-page
        codigo="419"
        titulo="Su sesión expiró"
        mensaje="Por seguridad cerramos la sesión tras un tiempo de inactividad. Vuelva a iniciar sesión para continuar." />
@endsection
