@props([])

<a href="{{ route('perfil.edit') }}" class="nav-link">
    <x-icon name="user-round" class="size-4 shrink-0" /> Mi perfil
</a>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="nav-link w-full text-left">
        <x-icon name="logout" class="size-4 shrink-0" /> Cerrar sesión
    </button>
</form>
