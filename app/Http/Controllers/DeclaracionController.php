<?php

namespace App\Http\Controllers;

use App\Actions\RegistrarDeclaracionConPago;
use App\Actions\RegistrarPagoADeclaracion;
use App\Http\Requests\StoreDeclaracionConPagoRequest;
use App\Http\Requests\StorePagoRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Declaracion;
use App\Services\NumeroDeclaracionGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeclaracionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);
        $query = Declaracion::with(['cliente', 'pagos']);
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($declarations) use ($search) {
                $declarations
                    ->where('numero_declaracion', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($client) use ($search) {
                        $client->where('nombres', 'like', "%{$search}%")
                            ->orWhere('apellidos', 'like', "%{$search}%");
                    });
            });
        }

        $addVisibleState = function (Declaracion $declaracion): Declaracion {
            $declaracion->setAttribute('estado', $declaracion->estadoVisible());

            return $declaracion;
        };

        if ($request->hasAny(['page', 'per_page', 'search'])) {
            return $query
                ->orderByDesc('id')
                ->paginate($filters['per_page'] ?? 10)
                ->withQueryString()
                ->through($addVisibleState);
        }

        return $query->get()->map($addVisibleState);
    }

    public function siguienteNumero(NumeroDeclaracionGenerator $generator): JsonResponse
    {
        return ApiResponse::success([
            'numero_declaracion' => $generator->generateSiguiente(),
        ], 'Siguiente número de declaración autogenerado');
    }

    public function crearConPago(StoreDeclaracionConPagoRequest $request, RegistrarDeclaracionConPago $registrar): JsonResponse
    {
        return response()->json($registrar->execute($request->validated()), 201);
    }

    public function registrarPago(StorePagoRequest $request, int $id, RegistrarPagoADeclaracion $action): JsonResponse
    {
        $resultado = $action->execute($id, $request->validated());

        return ApiResponse::success($resultado, 'Pago registrado correctamente', 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $declaracion = Declaracion::find($id);

        if (!$declaracion) {
            return ApiResponse::error('Declaración no encontrada', ['id' => ['La declaración especificada no existe.']], 404);
        }

        $declaracion->delete();

        return ApiResponse::success(null, 'Declaración eliminada correctamente');
    }
}