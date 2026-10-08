<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Declaracion;
use App\Models\Pago;

class DashboardController extends Controller
{
    public function estadisticas()
    {
        $declaraciones = Declaracion::query();
        $pagadas = (clone $declaraciones)->paid();
        $pendientes = (clone $declaraciones)->pending();
        $hoy = now();
        $inicioPeriodo = $hoy->copy()->startOfMonth()->subMonths(5);
        $finPeriodo = $hoy->copy()->endOfMonth();
        $actividad = [];

        for ($mes = $inicioPeriodo->copy(); $mes <= $finPeriodo; $mes->addMonth()) {
            $actividad[$mes->format('Y-m')] = [
                'mes' => $mes->format('Y-m'),
                'importaciones' => 0,
                'exportaciones' => 0,
            ];
        }

        foreach (Declaracion::query()
            ->whereBetween('fecha_declaracion', [$inicioPeriodo->toDateString(), $finPeriodo->toDateString()])
            ->get(['tipo', 'fecha_declaracion']) as $declaracion) {
            $mes = substr((string) $declaracion->fecha_declaracion, 0, 7);
            $tipo = match ($declaracion->tipo) {
                'Importacion' => 'importaciones',
                'Exportacion' => 'exportaciones',
                default => null,
            };

            if ($tipo !== null && isset($actividad[$mes])) {
                $actividad[$mes][$tipo]++;
            }
        }

        $distribucion = array_fill_keys(array_keys(config('aduanas.tipos_declaracion')), 0);
        foreach (Declaracion::query()
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->get() as $tipo) {
            if (array_key_exists($tipo->tipo, $distribucion)) {
                $distribucion[$tipo->tipo] = (int) $tipo->total;
            }
        }

        $recientes = Declaracion::with(['cliente', 'pagos'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Declaracion $declaracion) {
                $declaracion->setAttribute('estado', $declaracion->estadoVisible());

                return $declaracion;
            });

        return response()->json([
            'clientes_activos' => Cliente::where('activo', true)->count(),
            'clientes_total' => Cliente::count(),
            'declaraciones_total' => (clone $declaraciones)->count(),
            'declaraciones_mes' => (clone $declaraciones)
                ->whereBetween('fecha_declaracion', [$hoy->copy()->startOfMonth()->toDateString(), $hoy->toDateString()])
                ->count(),
            'declaraciones_pendientes' => $pendientes->count(),
            'declaraciones_pagadas' => $pagadas->count(),
            'pagos_total' => (float) Pago::sum('monto'),
            'operaciones_procesadas' => $pagadas->count(),
            'actividad_mensual' => array_values($actividad),
            'distribucion_tipo' => $distribucion,
            'declaraciones_recientes' => $recientes,
        ]);
    }
}
