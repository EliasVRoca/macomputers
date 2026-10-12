@extends('layouts.app')

@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('encabezado', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('subtitulo', 'Datos de la persona y de la cuenta de acceso.')
@section('acciones')
    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-3xl space-y-8">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <section class="space-y-5">
        <h2 class="text-sm font-medium text-ink-muted">Datos de la persona</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="nombre" class="label">Nombre completo</label>
                <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $persona->nombre) }}" required maxlength="150">
                @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ci" class="label">C.I.</label>
                <input id="ci" name="ci" class="input" value="{{ old('ci', $persona->ci) }}" required maxlength="20" placeholder="Ej. 1000004">
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
        </div>
    </section>

    <section class="space-y-5 border-t border-hairline pt-8">
        <h2 class="text-sm font-medium text-ink-muted">Cuenta de acceso</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="username" class="label">Usuario</label>
                <input id="username" name="username" class="input" value="{{ old('username', $usuario->username) }}" required maxlength="50" autocomplete="off" placeholder="Solo letras, números, guion y guion bajo">
                @error('username') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="label">Correo</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email', $usuario->email) }}" required maxlength="150">
                @error('email') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="label">Contraseña @if ($usuario->exists) <span class="font-normal text-ink-subtle">(dejar vacía para no cambiar)</span> @endif</label>
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
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="activo" value="1" class="h-4 w-4 rounded border-hairline accent-brand" @checked(old('activo', $usuario->activo))>
            Usuario activo <span class="text-ink-subtle">(puede iniciar sesión)</span>
        </label>
    </section>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
