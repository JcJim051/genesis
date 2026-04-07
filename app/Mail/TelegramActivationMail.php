<?php

namespace App\Mail;

use App\Models\Empleado;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TelegramActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Empleado $empleado;
    public string $link;

    public function __construct(Empleado $empleado, string $link)
    {
        $this->empleado = $empleado;
        $this->link = $link;
    }

    public function build()
    {
        return $this->subject('Activación de Telegram - Genesis SST')
            ->view('emails.telegram_activation');
    }
}
