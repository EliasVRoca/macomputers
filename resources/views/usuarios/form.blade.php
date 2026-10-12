@extends('layouts.app')

@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('encabezado', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('subtitulo', 'Datos de la persona y de la cuenta de acceso.')
@section('contenido')
<form method="POST" action="{{ $accion }}" class="card max-w-3xl space-y-6">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <fieldset class="grid gap-5 sm:grid-cols-2">
        <legend class="mb-3 text-sm font-medium text-ink-muted">Datos de la persona</legend>

        <div class="sm:col-span-2">
            <label for="nombre" class="label">Nombre completo</label>
            <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $persona->nombre) }}" required maxlength="150">
            @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="ci" class="label">C.I.</label>
            <input id="ci" name="ci" class="input" value="{{ old('ci', $persona->ci) }}" required maxlength="20">
            @error('ci') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="telefono" class="label">Teléfono</label>
            <input id="telefono" name="telefono" class="input" value="{{ old('telefono', $persona->telefono) }}" maxlength="30">
            @error('telefono') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="direccion" class="label">Dirección</label>
            <input id="direccion" name="direccion" class="input" value="{{ old('direccion', $persona->direccion) }}" maxlength="255">
            @error('direccion') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    </fieldset>

    <fieldset class="grid gap-5 sm:grid-cols-2">
        <legend class="mb-3 text-sm font-medium text-ink-muted">Cuenta de acceso</legend>

        <div>
            <label for="username" class="label">Usuario</label>
            <input id="username" name="username" class="input" value="{{ old('username', $usuario->username) }}" required maxlength="50" autocomplete="off">
            @error('username') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="label">Correo</label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email', $usuario->email) }}" required maxlength="150">
            @error('email') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="label">Contraseña {{ $usuario->exists ? '(dejar vacía para no cambiar)' : '' }}</label>
            <input id="password" name="password" type="password" class="input" autocomplete="new-password" {{ $usuario->exists ? '' : 'required' }}>
            @error('password') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="label">Repetir contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password">
        </div>
        <div>
            <label for="role_id" class="label">Rol</label>
            <select id="role_id" name="role_id" class="input" required>
                <option value="">Seleccione…</option>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->id }}" @selected(old('role_id', $usuario->role_id) == $rol->id)>{{ $rol->nombre }}</option>
                @endforeach
            </select>
            @error('role_id') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="sucursal_id" class="label">Sucursal</label>
            <select id="sucursal_id" name="sucursal_id" class="input" required>
                <option value="">Seleccione…</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $usuario->sucursal_id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
            @error('sucursal_id') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm sm:col-span-2">
            <input type="checkbox" name="activo" value="1" class="h-4 w-4 accent-ink" @checked(old('activo', $usuario->activo))>
            Usuario activo (puede iniciar sesión)
        </label>
    </fieldset>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
