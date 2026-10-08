<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $query = Pago::with(['declaracion.cliente']);
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('metodo_pago', 'like', "%{$search}%")
                  ->orWhere('estado', 'like', "%{$search}%")
                  ->orWhereHas('declaracion', function ($d) use ($search) {
                      $d->where('numero_declaracion', 'like', "%{$search}%")
                        ->orWhereHas('cliente', function ($c) use ($search) {
                            $c->where('nombres', 'like', "%{$search}%")
                              ->orWhere('apellidos', 'like', "%{$search}%");
                        });
                  });
            });
        }

        if ($request->hasAny(['page', 'per_page', 'search'])) {
            return $query
                ->orderByDesc('id')
                ->paginate($filters['per_page'] ?? 10)
                ->withQueryString();
        }

        return $query->orderByDesc('id')->get();
    }
}
