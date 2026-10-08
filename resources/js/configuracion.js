import { api } from './api.js';
import { escapeHtml, showConfirmDialog, statusBadge } from './ui.js';

export async function renderConfiguracion(container) {
    container.innerHTML = '<div class="loading-state">Cargando configuración del sistema…</div>';

    let me = null;
    let health = null;
    try {
        const [meRes, healthRes] = await Promise.all([
            api.getMe().catch(() => null),
            api.getHealth().catch(() => null),
        ]);
        me = meRes?.data;
        health = healthRes?.data;
    } catch {
        // Ignorar fallos de carga secundaria
    }

    const userName = me?.name || 'Usuario del sistema';
    const userEmail = me?.email || 'usuario@sistema-aduanero.gob';
    const userRole = me?.role || 'operador';
    const systemStatus = health?.status === 'HEALTHY' ? 'Operativo' : 'Verificando';
    const phpVersion = health?.php_version || '8.2+';

    container.innerHTML = `
        <div class="page-heading">
            <div>
                <p class="eyebrow">SISTEMA ADUANERO</p>
                <h1>Configuración</h1>
                <p class="page-description">Administra las preferencias, información del sistema y tu sesión activa.</p>
            </div>
        </div>

        <div class="config-grid">
            <!-- 1. Información del Sistema -->
            <article class="panel config-card">
                <header class="config-card-header">
                    <span class="config-icon">ℹ</span>
                    <div>
                        <h2>Información del sistema</h2>
                        <p>Consulta información general del Sistema Aduanero.</p>
                    </div>
                </header>
                <div class="config-card-body">
                    <div class="config-item">
                        <span>Nombre del Sistema</span>
                        <strong>Sistema Aduanero Enterprise</strong>
                    </div>
                    <div class="config-item">
                        <span>Versión del Software</span>
                        <strong>v1.4.0 (Laravel 12 Engine)</strong>
                    </div>
                    <div class="config-item">
                        <span>Estado del Servidor</span>
                        <span>${statusBadge('activo')} <strong>${escapeHtml(systemStatus)}</strong></span>
                    </div>
                    <div class="config-item">
                        <span>Entorno de Ejecución</span>
                        <strong>PHP ${escapeHtml(phpVersion)} · Database MySQL</strong>
                    </div>
                </div>
            </article>

            <!-- 2. Preferencias -->
            <article class="panel config-card">
                <header class="config-card-header">
                    <span class="config-icon">⚙</span>
                    <div>
                        <h2>Preferencias</h2>
                        <p>Personaliza diferentes aspectos de la interfaz.</p>
                    </div>
                </header>
                <div class="config-card-body">
                    <div class="config-item">
                        <div>
                            <strong>Tema de interfaz</strong>
                            <small>Modo claro predeterminado para alta legibilidad.</small>
                        </div>
                        <span class="status-badge is-active">Claro (Predeterminado)</span>
                    </div>
                    <div class="config-item">
                        <div>
                            <strong>Registros por página en tablas</strong>
                            <small>Cantidad de filas mostradas de forma predeterminada.</small>
                        </div>
                        <strong>10 registros</strong>
                    </div>
                    <div class="config-item">
                        <div>
                            <strong>Alertas de sistema</strong>
                            <small>Notificaciones sonoras y visuales activas.</small>
                        </div>
                        <span class="status-badge is-paid">Activado</span>
                    </div>
                </div>
            </article>

            <!-- 3. Cuenta de Usuario -->
            <article class="panel config-card">
                <header class="config-card-header">
                    <span class="config-icon">👤</span>
                    <div>
                        <h2>Cuenta</h2>
                        <p>Administra la información y preferencias de tu cuenta.</p>
                    </div>
                </header>
                <div class="config-card-body">
                    <div class="config-item">
                        <span>Nombre Completo</span>
                        <strong>${escapeHtml(userName)}</strong>
                    </div>
                    <div class="config-item">
                        <span>Correo Electrónico</span>
                        <strong>${escapeHtml(userEmail)}</strong>
                    </div>
                    <div class="config-item">
                        <span>Rol en el Sistema</span>
                        <span class="status-badge is-active">${escapeHtml(userRole.toUpperCase())}</span>
                    </div>
                </div>
            </article>

            <!-- 4. Sesión -->
            <article class="panel config-card config-card-danger">
                <header class="config-card-header">
                    <span class="config-icon config-icon-danger">↪</span>
                    <div>
                        <h2>Sesión</h2>
                        <p>Administra tu sesión actual en la plataforma.</p>
                    </div>
                </header>
                <div class="config-card-body">
                    <p class="config-session-text">Al cerrar sesión, deberás volver a ingresar tus credenciales para acceder al Sistema Aduanero.</p>
                    <button class="button button-danger" type="button" id="btnLogoutFromConfig">
                        <span>↪</span> Cerrar sesión
                    </button>
                </div>
            </article>
        </div>`;

    container.querySelector('#btnLogoutFromConfig')?.addEventListener('click', () => {
        showConfirmDialog({
            title: '¿Está seguro de que desea cerrar sesión?',
            message: 'Se cerrará tu sesión activa en el Sistema Aduanero y serás redirigido a la pantalla de inicio de sesión.',
            confirmText: 'Cerrar sesión',
            cancelText: 'Cancelar',
            onConfirm: () => {
                const logoutForm = document.querySelector('#logoutForm');
                if (logoutForm) {
                    logoutForm.submit();
                } else {
                    window.location.href = '/login';
                }
            },
        });
    });
}
