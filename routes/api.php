<?php

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeclaracionController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthCheckController::class, 'check']);

Route::middleware(['web', 'auth', 'throttle:api'])->group(function () {
    Route::get('/me', fn (Request $request) => \App\Http\Responses\ApiResponse::success($request->user()));

    Route::get('/catalogos', [CatalogoController::class, 'index']);
    Route::get('/dashboard/estadisticas', [DashboardController::class, 'estadisticas']);

    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::get('/clientes/{id}', [ClienteController::class, 'show']);
    Route::post('/clientes', [ClienteController::class, 'store']);
    Route::delete('/clientes/{id}', [ClienteController::class, 'destroy']);

    Route::get('/declaraciones', [DeclaracionController::class, 'index']);
    Route::get('/declaraciones/siguiente-numero', [DeclaracionController::class, 'siguienteNumero']);
    Route::post('/declaraciones/con-pago', [DeclaracionController::class, 'crearConPago']);
    Route::post('/declaraciones/{id}/pagos', [DeclaracionController::class, 'registrarPago']);
    Route::delete('/declaraciones/{id}', [DeclaracionController::class, 'destroy']);

    Route::get('/pagos', [PagoController::class, 'index']);

    // Notificaciones en el sistema
    Route::get('/notificaciones', [NotificationController::class, 'index']);
    Route::post('/notificaciones/{id}/leida', [NotificationController::class, 'markAsRead']);
    Route::post('/notificaciones/marcar-todas', [NotificationController::class, 'markAllAsRead']);

    // Gestión de usuarios (requiere permiso administrar-usuarios)
    Route::middleware(['can:administrar-usuarios'])->group(function () {
        Route::get('/usuarios', [UserController::class, 'index']);
        Route::get('/usuarios/{id}', [UserController::class, 'show']);
        Route::post('/usuarios', [UserController::class, 'store']);
        Route::put('/usuarios/{id}', [UserController::class, 'update']);
        Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);
    });
});