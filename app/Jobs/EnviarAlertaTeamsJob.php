<?php

namespace App\Jobs;

use App\Models\Alerta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notifica una alerta de riesgo FMEA al canal de Teams configurado, vía el
 * webhook de un flujo de Power Automate ("When a Teams webhook request is
 * received" + "Post card in a chat or channel"). No bloquea el guardado de
 * la evaluación: se despacha desde AlertaObserver y corre en la cola.
 */
class EnviarAlertaTeamsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public Alerta $alerta)
    {
    }

    public function handle(): void
    {
        $webhookUrl = config('services.teams.webhook_url');

        if (!$webhookUrl) {
            Log::info('EnviarAlertaTeamsJob: sin TEAMS_ALERTAS_WEBHOOK_URL configurado, se omite el envío.');
            return;
        }

        $this->alerta->loadMissing('evaluacion.activo');
        $evaluacion = $this->alerta->evaluacion;
        $activo = $evaluacion?->activo;

        if (!$evaluacion || !$activo) {
            Log::warning('EnviarAlertaTeamsJob: alerta sin evaluación o activo asociado, se omite el envío.', [
                'id_alerta' => $this->alerta->id_alerta,
            ]);
            return;
        }

        $payload = [
            'activo' => $activo->nombre_equipo,
            'codigo' => $activo->codigo_patrimonial,
            'score' => (float) $evaluacion->score_calculado,
            'nivel' => $evaluacion->nivel_riesgo,
            'fecha' => optional($evaluacion->fecha_evaluacion)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
            'url' => route('admin.activos.show', $activo->id_activo),
        ];

        $respuesta = Http::timeout(10)->post($webhookUrl, $payload);

        if ($respuesta->failed()) {
            Log::error('EnviarAlertaTeamsJob: el webhook de Teams respondió con error.', [
                'id_alerta' => $this->alerta->id_alerta,
                'status' => $respuesta->status(),
                'body' => $respuesta->body(),
            ]);
            $this->fail('Webhook de Teams respondió ' . $respuesta->status());
        }
    }
}
