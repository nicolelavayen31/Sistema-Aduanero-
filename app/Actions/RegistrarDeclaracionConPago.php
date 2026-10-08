<?php

namespace App\Actions;

use App\Models\Declaracion;
use App\Models\Pago;
use App\Services\NumeroDeclaracionGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarDeclaracionConPago
{
    public function __construct(private NumeroDeclaracionGenerator $generator)
    {
    }

    public function execute(array $datos): array
    {
        return DB::transaction(function () use ($datos): array {
            $numeroDeclaracion = trim((string) ($datos['numero_declaracion'] ?? ''));
            if ($numeroDeclaracion === '' || $numeroDeclaracion === 'AUTO') {
                $numeroDeclaracion = $this->generator->generateSiguiente();
            }

            // Validar límite de pago inicial vs valor de mercancía
            $valorMercanciaCentavos = (int) round((float) $datos['valor_mercancia'] * 100);
            $montoPagoCentavos = (int) round((float) $datos['monto'] * 100);

            if ($montoPagoCentavos > $valorMercanciaCentavos) {
                throw ValidationException::withMessages([
                    'monto' => ['El monto del pago inicial no puede ser superior al valor total de la mercancía.'],
                ]);
            }

            $declaracion = Declaracion::create([
                'cliente_id' => $datos['cliente_id'],
                'numero_declaracion' => $numeroDeclaracion,
                'tipo' => $datos['tipo'],
                'valor_mercancia' => $datos['valor_mercancia'],
                'estado' => 'pendiente',
                'fecha_declaracion' => now()->toDateString(),
            ]);

            $estadoPago = $datos['estado'] ?? 'pagado';
            $pago = Pago::create([
                'declaracion_id' => $declaracion->id,
                'monto' => $datos['monto'],
                'metodo_pago' => $datos['metodo_pago'],
                'estado' => $estadoPago,
                'fecha_pago' => $estadoPago === 'pagado' ? now() : null,
            ]);

            if ($estadoPago === 'pagado' && $montoPagoCentavos >= $valorMercanciaCentavos) {
                $declaracion->update(['estado' => 'pagado']);
            }

            $declaracion->setAttribute('estado', $declaracion->estadoVisible());

            if (auth()->check()) {
                auth()->user()->notify(new \App\Notifications\DeclaracionRegistradaNotification($declaracion, $pago));
            }

            return [
                'message' => 'Declaración y pago creados correctamente',
                'declaracion' => $declaracion,
                'pago' => $pago,
            ];
        });
    }
}
