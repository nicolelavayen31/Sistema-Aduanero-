<?php

namespace App\Actions;

use App\Models\Declaracion;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarPagoADeclaracion
{
    public function execute(int $declaracionId, array $datos): array
    {
        return DB::transaction(function () use ($declaracionId, $datos): array {
            /** @var Declaracion $declaracion */
            $declaracion = Declaracion::with('pagos')->lockForUpdate()->find($declaracionId);

            if (!$declaracion) {
                throw ValidationException::withMessages([
                    'declaracion_id' => ['La declaración aduanera especificada no existe.'],
                ]);
            }

            if (in_array($declaracion->estado, ['cancelado', 'rechazado'], true)) {
                throw ValidationException::withMessages([
                    'declaracion' => ["No se pueden registrar pagos a una declaración en estado '{$declaracion->estado}'."],
                ]);
            }

            $valorMercanciaCentavos = (int) round((float) $declaracion->valor_mercancia * 100);
            $totalPagadoActualCentavos = (int) round(
                (float) $declaracion->pagos->where('estado', 'pagado')->sum(fn (Pago $p) => (float) $p->monto) * 100
            );

            if ($declaracion->estadoVisible() === 'pagado') {
                throw ValidationException::withMessages([
                    'monto' => ['La declaración ya se encuentra completamente pagada.'],
                ]);
            }

            $nuevoPagoCentavos = (int) round((float) $datos['monto'] * 100);
            $saldoPendienteCentavos = max(0, $valorMercanciaCentavos - $totalPagadoActualCentavos);

            if ($nuevoPagoCentavos > $saldoPendienteCentavos) {
                $saldoFormateado = number_format($saldoPendienteCentavos / 100, 2);
                throw ValidationException::withMessages([
                    'monto' => ["El monto del pago (\${$datos['monto']}) supera el saldo pendiente (\${$saldoFormateado})."],
                ]);
            }

            $estadoPago = $datos['estado'] ?? 'pagado';
            $pago = Pago::create([
                'declaracion_id' => $declaracion->id,
                'monto' => $datos['monto'],
                'metodo_pago' => $datos['metodo_pago'],
                'estado' => $estadoPago,
                'fecha_pago' => $estadoPago === 'pagado' ? now() : null,
            ]);

            if ($estadoPago === 'pagado' && ($totalPagadoActualCentavos + $nuevoPagoCentavos) >= $valorMercanciaCentavos) {
                $declaracion->update(['estado' => 'pagado']);
            }

            $declaracion->load('pagos');
            $declaracion->setAttribute('estado', $declaracion->estadoVisible());

            if (auth()->check()) {
                auth()->user()->notify(new \App\Notifications\DeclaracionRegistradaNotification($declaracion, $pago));
            }

            return [
                'message' => 'Pago registrado correctamente',
                'declaracion' => $declaracion,
                'pago' => $pago,
            ];
        });
    }
}
