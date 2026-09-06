<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Verifica que el usuario autenticado tenga permiso al módulo indicado.
     *
     * Uso en rutas: ->middleware('modulo:activos')
     *
     * @param string $modulo  Clave del módulo (dashboard, activos, incidentes, riesgos, mantenimiento, informes, usuarios)
     */
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        $user = $request->user();

        if (!$user || !$user->tieneAccesoModulo($modulo)) {
            abort(403, 'No tienes permiso para acceder a este módulo.');
        }

        return $next($request);
    }
}
