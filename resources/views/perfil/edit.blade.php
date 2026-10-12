@extends('layouts.app')

@section('titulo', 'Mi perfil')
@section('encabezado', 'Mi perfil')
@section('subtitulo', 'Sus datos de acceso y de contacto.')

@section('contenido')
<div class="grid max-w-4xl gap-6 lg:grid-cols-2">
    {{-- Cuenta (solo lectura) --}}
    <section class="card card-pad lg:col-span-2">
        <h2 class="mb-4 text-sm font-medium text-ink-muted">Cuenta</h2>
        <div class="flex items-center gap-4">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand text-xl font-medium text-on-primary">
                {{ mb_strtoupper(mb_substr($usuario->nombre(), 0, 1)) }}
            </span>
            <dl class="grid flex-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-ink-muted">Usuario</dt>
                    <dd class="font-medium">{{ $usuario->username }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">C.I.</dt>
                    <dd class="font-medium">{{ $usuario->persona?->ci ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Rol</dt>
                    <dd><span class="badge badge-brand">{{ $usuario->role?->nombre }}</span></dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Sucursal</dt>
                    <dd class="font-medium">{{ $usuario->sucursal?->nombre ?? 'Todas las sucursales' }}</dd>
                </div>
            </dl>
        </div>
        <p class="mt-4 text-xs text-ink-subtle">El usuario, el C.I., el rol y la sucursal los administra el gerente.</p>
    </section>

    {{-- Datos de contacto --}}
    <section class="card card-pad">
        <h2 class="mb-4 text-sm font-medium text-ink-muted">Datos de contacto</h2>
        <form method="POST" action="{{ route('perfil.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="label">Nombre completo</label>
                <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $usuario->persona?->nombre) }}" required maxlength="150">
                @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="label">Correo</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email', $usuario->email) }}" required maxlength="150">
                @error('email') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="telefono" class="label">Teléfono</label>
                <input id="telefono" name="telefono" class="input" value="{{ old('telefono', $usuario->persona?->telefono) }}" maxlength="30">
                @error('telefono') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="direccion" class="label">Dirección</label>
                <input id="direccion" name="direccion" class="input" value="{{ old('direccion', $usuario->persona?->direccion) }}" maxlength="255">
                @error('direccion') <p class="error-texto">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar datos</button>
        </form>
    </section>

    {{-- Contraseña --}}
    <section class="card card-pad">
        <h2 class="mb-4 text-sm font-medium text-ink-muted">Contraseña</h2>
        <form method="POST" action="{{ route('perfil.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="label">Contraseña actual</label>
                <input id="current_password" name="current_password" type="password" class="input" autocomplete="current-password" required>
                @error('current_password') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="label">Nueva contraseña</label>
                <input id="password" name="password" type="password" class="input" autocomplete="new-password" required>
                @error('password') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Repetir la nueva contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" required>
            </div>
            <p class="text-xs text-ink-subtle">Mínimo 8 caracteres.</p>

            <button type="submit" class="btn btn-primary"><x-icon name="key-round" class="size-4" /> Cambiar contraseña</button>
        </form>
    </section>

    {{-- Sesiones --}}
    <section class="card card-pad lg:col-span-2">
        <h2 class="mb-2 text-sm font-medium text-ink-muted">Sesiones</h2>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-ink-muted">
                El sistema permite una sola sesión activa por usuario. Si ingresó desde otro equipo, la anterior se cerró.
                Puede cerrar aquí cualquier sesión abierta que no sea esta.
            </p>
            <form method="POST" action="{{ route('perfil.sesiones') }}" onsubmit="return confirm('¿Cerrar la sesión en los demás equipos?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary"><x-icon name="logout" class="size-4" /> Cerrar las demás sesiones</button>
            </form>
        </div>
    </section>
</div>
@endsection
