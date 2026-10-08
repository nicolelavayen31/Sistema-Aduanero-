<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Notifications\UserWelcomeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'role' => ['sometimes', 'nullable', 'string', 'in:admin,operador,auditor'],
        ]);

        $query = User::query();

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if ($request->hasAny(['page', 'per_page', 'search', 'role'])) {
            $paginator = $query->orderBy('id', 'desc')->paginate($filters['per_page'] ?? 10);
            return ApiResponse::paginated($paginator, 'Usuarios obtenidos correctamente');
        }

        return ApiResponse::success($query->orderBy('id', 'desc')->get(), 'Lista de usuarios');
    }

    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return ApiResponse::error('Usuario no encontrado', ['id' => ['El ID del usuario especificado no existe.']], 404);
        }

        return ApiResponse::success($user, 'Detalles del usuario');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $plainPassword = $validated['password'];

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($plainPassword),
            'role' => $validated['role'],
        ]);

        try {
            $user->notify(new UserWelcomeNotification($plainPassword));
        } catch (\Throwable $e) {
            // Ignorar fallos de red en servidor SMTP local de desarrollo
        }

        return ApiResponse::success($user, 'Usuario creado exitosamente', 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return ApiResponse::error('Usuario no encontrado', ['id' => ['El ID del usuario especificado no existe.']], 404);
        }

        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return ApiResponse::success($user->fresh(), 'Usuario actualizado correctamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return ApiResponse::error('Usuario no encontrado', ['id' => ['El ID del usuario especificado no existe.']], 404);
        }

        if ($user->id === auth()->id()) {
            return ApiResponse::error('Operación no permitida', ['user' => ['No puedes eliminar tu propio usuario en uso.']], 400);
        }

        $user->delete();

        return ApiResponse::success(null, 'Usuario eliminado correctamente');
    }
}
