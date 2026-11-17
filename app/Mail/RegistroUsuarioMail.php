<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistroUsuarioMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Usuario $usuario;
    public string $saludo;

    /**
     * Create a new message instance.
     */
    public function __construct(Usuario $usuario)
    {
        $this->usuario = $usuario;

        $hora = (int) now()->format('H');
        if ($hora >= 5 && $hora < 12) {
            $this->saludo = 'Buenos días';
        } elseif ($hora >= 12 && $hora < 19) {
            $this->saludo = 'Buenas tardes';
        } else {
            $this->saludo = 'Buenas noches';
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registro de Usuario - Cuenta Pendiente de Habilitación',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.registro_usuario',
            with: [
                'usuario' => $this->usuario,
                'saludo' => $this->saludo,
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
