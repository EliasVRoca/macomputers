<?php

namespace App\Http\Controllers;

use App\Support\Menu;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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
