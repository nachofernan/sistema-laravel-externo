<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $temporaryPassword;

    public function __construct($temporaryPassword)
    {
        $this->temporaryPassword = $temporaryPassword;
    }

    public function build()
    {
        return $this->markdown('emails.temporary-password')
            ->subject('Su contraseña temporal para acceso al sistema');
    }
}
