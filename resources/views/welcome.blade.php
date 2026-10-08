<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0c3035">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Sistema Aduanero</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app-shell">
            <aside class="sidebar" id="sidebar">
                <a class="brand" href="#" data-view="dashboard" aria-label="Sistema Aduanero, inicio">
                    <span class="brand-mark" aria-hidden="true">SA</span>
                    <span class="brand-copy"><strong>Sistema Aduanero</strong><small>GESTIÓN ADUANERA</small></span>
                </a>
                <div class="nav-label">PRINCIPAL</div>
                <nav class="main-nav" aria-label="Navegación principal">
                    <button class="nav-link is-active" type="button" data-view="dashboard">
                        <span class="nav-icon">◫</span><span>Dashboard</span>
                    </button>
                    <button class="nav-link" type="button" data-view="clientes">
                        <span class="nav-icon">♙</span><span>Clientes</span>
                    </button>
                    <button class="nav-link" type="button" data-view="declaraciones">
                        <span class="nav-icon">▤</span><span>Declaraciones</span>
                    </button>
                    <button class="nav-link" type="button" data-view="pagos">
                        <span class="nav-icon">💳</span><span>Pagos</span>
                    </button>
                </nav>
                <div class="nav-label nav-label-system">SISTEMA</div>
                <nav class="main-nav" aria-label="Navegación de sistema">
                    <button class="nav-link" type="button" data-view="configuracion">
                        <span class="nav-icon">⚙</span><span>Configuración</span>
                    </button>
                </nav>
                <div class="sidebar-bottom">
                    <div class="sidebar-help">
                        <span class="help-icon">i</span>
                        <div><strong>Sistema Aduanero</strong><small>Gestión de clientes y operaciones.</small></div>
                    </div>
                </div>
            </aside>

            <main class="main-area">
                <header class="topbar">
                    <button class="menu-toggle" id="menuToggle" type="button" aria-label="Abrir menú">☰</button>
                    <div class="breadcrumb"><span>Operaciones</span><span class="breadcrumb-separator">/</span><strong id="breadcrumbCurrent">Dashboard</strong></div>
                    <form class="global-search" id="globalSearchForm" role="search">
                        <label class="visually-hidden" for="globalSearch">Buscar declaraciones</label>
                        <span aria-hidden="true">⌕</span>
                        <input id="globalSearch" type="search" placeholder="Buscar declaración…" autocomplete="off">
                    </form>
                    <div class="topbar-right"><span class="connection-indicator" id="apiStatus"><span></span><span id="apiStatusLabel">Conectando…</span></span><span class="topbar-date" id="currentDate"></span></div>
                </header>
                <section class="page-container">
                    <div id="pageContent" aria-live="polite"></div>
                </section>
            </main>
        </div>

        <form id="logoutForm" method="POST" action="{{ route('logout') }}" style="display: none;">
            @csrf
        </form>

        <div id="modalRoot"></div>
        <div id="toastRegion" class="toast-region" aria-live="polite" aria-atomic="true"></div>
    </body>
</html>
