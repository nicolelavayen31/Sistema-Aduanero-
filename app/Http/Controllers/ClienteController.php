<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);
        $query = Cliente::query();
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($clients) use ($search) {
                foreach (['nombres', 'apellidos', 'cedula', 'email', 'telefono'] as $column) {
                    $clients->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        if ($request->hasAny(['page', 'per_page', 'search'])) {
            return $query
                ->orderBy('id')
                ->paginate($filters['per_page'] ?? 10)
                ->withQueryString();
        }

        return $query->orderBy('id')->get();
    }

    public function show($id): JsonResponse
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return ApiResponse::error('Cliente no encontrado', ['id' => ['El cliente especificado no existe.']], 404);
        }

        return response()->json($cliente, 200);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::create($request->validated());

        return response()->json($cliente, 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $cliente = Cliente::withCount('declaraciones')->find($id);

        if (!$cliente) {
            return ApiResponse::error('Cliente no encontrado', ['id' => ['El cliente especificado no existe.']], 404);
        }

        if ($cliente->declaraciones_count > 0) {
            return ApiResponse::error(
                'No se puede eliminar el cliente',
                ['declaraciones' => ["El cliente posee {$cliente->declaraciones_count} declaración(es) registrada(s). No es posible eliminarlo para preservar el historial aduanero."]],
                400
            );
        }

        $cliente->delete();

        return ApiResponse::success(null, 'Cliente eliminado correctamente');
    }
}