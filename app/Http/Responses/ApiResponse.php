<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Operación realizada con éxito', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'errors' => [],
        ], $code);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $message = 'Listado obtenido correctamente'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'errors' => [],
        ], 200);
    }

    public static function error(string $message = 'Error en la solicitud', array $errors = [], int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $code);
    }
}
