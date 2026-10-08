<?php

use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeadersMiddleware::class);

        $middleware->trimStrings(except: [
            'cedula',
            'telefono',
            'email',
            'numero_declaracion',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    'Error de validación en los datos enviadas.',
                    $e->errors(),
                    422
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    'No autorizado. Token o sesión no válida.',
                    ['auth' => ['Debe iniciar sesión para acceder a este recurso.']],
                    401
                );
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    'Acceso denegado. No tiene permisos suficientes.',
                    ['role' => ['Su rol no permite realizar esta acción.']],
                    403
                );
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    'Recurso no encontrado.',
                    ['resource' => ['El elemento solicitado no existe.']],
                    404
                );
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $message = config('app.debug') ? $e->getMessage() : 'Error interno del servidor.';
                return ApiResponse::error(
                    'Error procesando la solicitud.',
                    ['server' => [$message]],
                    500
                );
            }
        });
    })->create();