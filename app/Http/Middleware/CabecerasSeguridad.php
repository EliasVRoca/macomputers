<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas (OWASP A02 / ASVS 5.0 · V14).
 *
 * La CSP se omite en local para no romper Vite/HMR (sirve scripts desde otro
 * puerto). En producción y pruebas se envía; todavía permite `unsafe-inline`
 * porque las vistas usan scripts y handlers inline (deuda técnica documentada).
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('X-Frame-Options', 'DENY');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        $csp = $this->csp();

        if ($csp !== null) {
            $respuesta->headers->set('Content-Security-Policy', $csp);
        }

        // HSTS solo tiene sentido sobre HTTPS; en HTTP el navegador lo ignora.
        if ($request->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $respuesta;
    }

    private function csp(): ?string
    {
        if (app()->environment('local')) {
            return null;
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline'",
            "script-src 'self' 'unsafe-inline'",
            "connect-src 'self'",
        ]);
    }
}
