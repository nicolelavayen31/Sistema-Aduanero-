<?php

namespace App\Console\Commands;

use App\Models\Declaracion;
use App\Models\User;
use App\Notifications\DeclaracionesPendientesReminderNotification;
use Illuminate\Console\Command;

class NotificarDeclaracionesPendientes extends Command
{
    protected $signature = 'aduanas:notificar-pendientes';

    protected $description = 'Envía notificaciones periódicas sobre las declaraciones aduaneras pendientes de pago';

    public function handle(): int
    {
        $pendientes = Declaracion::with('cliente')
            ->pending()
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info('No hay declaraciones pendientes para notificar.');
            return 0;
        }

        $resumen = $pendientes->map(fn ($d) => [
            'numero' => $d->numero_declaracion,
            'cliente' => $d->cliente ? "{$d->cliente->nombres} {$d->cliente->apellidos}" : 'N/A',
            'valor' => $d->valor_mercancia,
        ])->toArray();

        $destinatarios = User::whereIn('role', ['admin', 'operador'])->get();

        foreach ($destinatarios as $user) {
            $user->notify(new DeclaracionesPendientesReminderNotification($pendientes->count(), $resumen));
        }

        $this->info("Se enviaron notificaciones de {$pendientes->count()} declaraciones pendientes a {$destinatarios->count()} usuarios.");
        return 0;
    }
}
