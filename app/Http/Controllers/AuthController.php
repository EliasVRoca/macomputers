<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function mostrarLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Ingrese su usuario.',
            'password.required' => 'Ingrese su contraseña.',
        ]);

        if (! Auth::attempt([...$credenciales, 'activo' => true])) {
            return back()
                ->withErrors(['username' => 'Usuario o contraseña incorrectos, o usuario inactivo.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        // Sesión única por usuario (RNF3): se cierran las sesiones anteriores de este usuario.
        DB::table('sessions')
            ->where('user_id', Auth::id())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return redirect()->intended(route('inicio'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
