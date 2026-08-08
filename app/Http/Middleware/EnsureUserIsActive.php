<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Expulsa a los usuarios cuya cuenta fue desactivada (estado = 0) después de
 * haber iniciado sesión.
 *
 * AuthController::login() solo verifica el estado en el momento del ingreso.
 * Sin esta comprobación por petición quedan dos huecos: una sesión ya abierta
 * sobrevive a la desactivación hasta que el usuario cierre sesión, y la cookie
 * de "Recordarme" reautentica sin pasar por login(), por lo que nunca se
 * evaluaría el estado.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && (int) $usuario->estado === 0) {
            // Auth::logout() además rota el remember_token e invalida la cookie
            // de sesión persistente, de modo que la expulsión sea definitiva.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'correo' => 'Tu cuenta se encuentra desactivada. Contacta al administrador del sistema.',
            ]);
        }

        return $next($request);
    }
}
