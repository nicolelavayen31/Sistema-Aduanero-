<?php

namespace App\Notifications;

use App\Models\Declaracion;
use App\Models\Pago;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeclaracionRegistradaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Declaracion $declaracion,
        public Pago $pago
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Registro de Declaración {$this->declaracion->numero_declaracion}")
            ->line("Se ha registrado una nueva declaración aduanera de tipo [{$this->declaracion->tipo}].")
            ->line("Número: {$this->declaracion->numero_declaracion}")
            ->line("Valor Mercancía: \${$this->declaracion->valor_mercancia}")
            ->line("Estado Declaración: {$this->declaracion->estado}")
            ->line("Pago Registrado: \${$this->pago->monto} ({$this->pago->metodo_pago})");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Declaración {$this->declaracion->numero_declaracion} registrada",
            'message' => "Declaración tipo {$this->declaracion->tipo} con pago de \${$this->pago->monto}.",
            'declaracion_id' => $this->declaracion->id,
            'numero_declaracion' => $this->declaracion->numero_declaracion,
            'estado' => $this->declaracion->estado,
        ];
    }
}
