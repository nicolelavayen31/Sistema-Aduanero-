<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0c3035">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Iniciar sesión · Sistema Aduanero</title>
        @vite('resources/css/login.css')
    </head>
    <body class="auth-page">
        <main class="login-card">
            <a class="login-brand" href="{{ route('login') }}">
                <span class="login-brand-mark" aria-hidden="true">SA</span>
                <span><strong>Sistema Aduanero</strong><small>GESTIÓN ADUANERA</small></span>
            </a>
            <div class="login-intro">
                <p class="login-eyebrow">ACCESO SEGURO</p>
                <h1>Bienvenido</h1>
                <p>Inicia sesión para gestionar clientes y operaciones.</p>
            </div>
            @if ($errors->any())
                <div class="login-error" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('login.store') }}" class="login-form">
                @csrf
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <label class="remember-option"><input type="checkbox" name="remember"> Mantener sesión iniciada</label>
                <button type="submit">Iniciar sesión</button>
            </form>
        </main>
    </body>
</html>
