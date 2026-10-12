<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\CheckModuleAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'modulo' => CheckModuleAccess::class,
        ]);

        // Se aplica a todo el grupo web (no solo a las rutas protegidas) para
        // que ninguna ruta futura quede fuera; es inofensivo para invitados,
        // donde $request->user() es null.
        $middleware->web(append: [
            EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejo amigable de sesión y token CSRF expirados (Error 419)
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'expired' => true,
                    'message' => 'Tu sesión ha expirado tras 30 minutos de inactividad.',
                    'redirect' => route('login'),
                ], 419);
            }

            return redirect()->route('login')
                ->with('warning', 'Tu sesión ha expirado tras 30 minutos de inactividad por seguridad. Por favor, inicia sesión nuevamente.');
        });

        // Manejo amigable de solicitudes no autenticadas (sesión finalizada)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'expired' => true,
                    'message' => 'Tu sesión ha finalizado. Por favor, inicia sesión nuevamente.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->guest(route('login'))
                ->with('warning', 'Tu sesión ha expirado tras 30 minutos de inactividad. Por favor, inicia sesión nuevamente.');
        });
    })->create();
