<?php

namespace App\Mail;

use App\Models\Alerta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaCriticaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Alerta $alerta;

    /**
     * Create a new message instance. Se espera que la relación
     * 'evaluacion.activo' ya venga cargada (ver AlertaObserver).
     */
    public function __construct(Alerta $alerta)
    {
        $this->alerta = $alerta;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $activo = $this->alerta->evaluacion?->activo;

        return new Envelope(
            subject: '⚠️ Alerta de Riesgo Crítico — ' . ($activo->codigo_patrimonial ?? 'Activo Tecnológico'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.alerta_critica',
            with: [
                'evaluacion' => $this->alerta->evaluacion,
                'activo' => $this->alerta->evaluacion?->activo,
                'modoFalla' => $this->alerta->evaluacion?->modoFalla,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
