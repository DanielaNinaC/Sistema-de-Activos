<?php

namespace App\Observers;

// use App\Jobs\EnviarAlertaTeamsJob; // Teams deshabilitado temporalmente
use App\Mail\AlertaCriticaMail;
use App\Models\Alerta;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertaObserver
{
    /**
     * Notifica (por ahora solo correo) las alertas de riesgo Crítico.
     * El envío a Teams está deshabilitado temporalmente (ver abajo).
     * 'tipo_alerta' en este sistema guarda el COLOR del semáforo
     * (Roja/Naranja/Amarilla/Verde — ver MotorFmeaService::obtenerNivelYAlerta()),
     * no el nivel de riesgo; el nivel real ('Crítico'/'Alto'/'Medio'/'Bajo')
     * vive en evaluacion_riesgo.nivel_riesgo. 'Crítico' equivale al color 'Roja'.
     */
    public function created(Alerta $alerta): void
    {
        $alerta->loadMissing('evaluacion.activo', 'evaluacion.modoFalla');

        if ($alerta->evaluacion?->nivel_riesgo !== 'Crítico') {
            return;
        }

        // EnviarAlertaTeamsJob::dispatch($alerta); // Teams deshabilitado temporalmente: solo correo por ahora

        // Destinatarios dinámicos: todo usuario activo con rol Administrador
        // o Soporte (config/roles.php), no una lista fija en .env. El envío
        // sigue saliendo desde MAIL_FROM_ADDRESS (el Gmail de soporte).
        $destinatarios = Usuario::whereIn('fk_id_rol', [config('roles.admin'), config('roles.soporte')])
            ->where('estado', true)
            ->pluck('correo')
            ->filter()
            ->values()
            ->all();

        if (!empty($destinatarios)) {
            Mail::to($destinatarios)->queue(new AlertaCriticaMail($alerta));
        } else {
            Log::warning('AlertaObserver: no hay usuarios Administrador/Soporte activos para notificar la alerta crítica.');
        }
    }
}
