<?php

namespace App\Http\Controllers;

use App\Support\Menu;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InicioController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $ruta = Menu::primerModulo($request->user());

        if ($ruta) {
            return redirect()->route($ruta);
        }

        return view('inicio');
    }
}
