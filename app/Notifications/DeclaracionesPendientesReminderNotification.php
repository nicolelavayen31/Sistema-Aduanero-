<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeclaracionesPendientesReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $totalPendientes,
        public array $resumen
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Recordatorio: Declaraciones Aduaneras Pendientes de Pago')
            ->greeting("Estimado(a) {$notifiable->name},")
            ->line("Se registra un total de {$this->totalPendientes} declaración(es) pendiente(s) de pago o proceso en el sistema.")
            ->line('Resumen de números de declaración pendientes:');

        foreach ($this->resumen as $item) {
            $mail->line("- N° {$item['numero']}: Cliente [{$item['cliente']}] por \${$item['valor']}");
        }

        return $mail->action('Revisar en el Sistema', url('/declaraciones'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Alerta de Declaraciones Pendientes',
            'message' => "Existen {$this->totalPendientes} declaración(es) pendientes por procesar o pagar.",
            'total_pendientes' => $this->totalPendientes,
        ];
    }
}
