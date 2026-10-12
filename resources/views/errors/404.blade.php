@extends(auth()->check() ? 'layouts.app' : 'layouts.blank')

@section('titulo', 'Página no encontrada')
@section('encabezado', 'Página no encontrada')

@section('contenido')
    <x-error-page
        codigo="404"
        titulo="No encontramos esta página"
        mensaje="La dirección no existe o el registro fue eliminado. Verifique el enlace o vuelva al inicio." />
@endsection
