<?php

namespace App\Http\Controllers;

use App\Services\NotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    protected NotificacionService $notificacionService;

    public function __construct(NotificacionService $notificacionService)
    {
        $this->notificacionService = $notificacionService;
    }

    /**
     * Obtener lista de notificaciones en formato JSON.
     */
    public function recientes(Request $request): JsonResponse
    {
        $usuario = Auth::user();
        $datos = $this->notificacionService->obtenerParaUsuario($usuario);

        $leidasAt = session('notificaciones_leidas_at');
        if ($leidasAt) {
            // Filtrar las leídas para el conteo no leído si se desea
            $datos['leidas_at'] = $leidasAt;
        }

        return response()->json($datos);
    }

    /**
     * Marcar todas las notificaciones como leídas en la sesión actual.
     */
    public function marcarLeidas(Request $request): JsonResponse
    {
        session(['notificaciones_leidas_at' => now()->toIso8601String()]);

        return response()->json([
            'success' => true,
            'message' => 'Notificaciones marcadas como leídas'
        ]);
    }
}
