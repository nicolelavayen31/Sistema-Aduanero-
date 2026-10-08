<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(public string $plainPassword)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenido al Sistema Aduanero')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line('Se ha creado tu cuenta de acceso en el Sistema Aduanero.')
            ->line("Rol asignado: {$notifiable->role}")
            ->line("Tu contraseña temporal: {$this->plainPassword}")
            ->action('Ingresar al Sistema', url('/login'))
            ->line('Por motivos de seguridad, te recomendamos cambiar tu contraseña al ingresar.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Cuenta creada exitosamente',
            'message' => "Se ha asignado el rol [{$notifiable->role}] a tu usuario.",
            'user_id' => $notifiable->id,
            'role' => $notifiable->role,
        ];
    }
}
