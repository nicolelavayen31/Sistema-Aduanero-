import { api } from './api.js';
import { escapeHtml, formatDate, formatMoney, statusBadge } from './ui.js';

const monthFormatter = new Intl.DateTimeFormat('es-EC', { month: 'short' });

function parseDate(value) {
    if (!value) return null;
    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number);
    const date = year && month && day ? new Date(year, month - 1, day) : new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
}

function monthLabel(value) {
    const date = parseDate(`${value}-01`);
    return date ? monthFormatter.format(date).replace('.', '') : value;
}

function activityChart(activity, categories) {
    const months = activity.map((month) => ({ ...month, label: monthLabel(month.mes) }));
    const maximum = Math.max(1, ...months.flatMap(({ importaciones, exportaciones }) => [importaciones, exportaciones]));
    const axisMaximum = Math.max(4, Math.ceil(maximum / 4) * 4);
    const ticks = [0, 1, 2, 3, 4].map((index) => Math.round(axisMaximum * (4 - index) / 4));
    const bars = months.map(({ label, importaciones, exportaciones }) => `
        <div class="chart-month" aria-label="${escapeHtml(label)}: ${importaciones} ${escapeHtml(categories[0]?.label || 'Importación')}, ${exportaciones} ${escapeHtml(categories[1]?.label || 'Exportación')}">
            <div class="chart-bars">
                <span class="chart-bar chart-bar-import" style="--bar-height:${(importaciones / axisMaximum) * 100}%"></span>
                <span class="chart-bar chart-bar-export" style="--bar-height:${(exportaciones / axisMaximum) * 100}%"></span>
            </div>
            <span class="chart-month-label">${escapeHtml(label)}</span>
        </div>`).join('');

    return `<div class="activity-chart" role="img" aria-label="Declaraciones por tipo durante los últimos seis meses">
        <div class="chart-axis">${ticks.map((tick) => `<span>${tick}</span>`).join('')}</div>
        <div class="chart-plot">
            <div class="chart-gridlines">${ticks.slice(0, -1).map(() => '<span></span>').join('')}<span></span></div>
            <div class="chart-months">${bars}</div>
        </div>
    </div>
    <div class="chart-legend">
        <span><i class="legend-dot legend-import"></i>${escapeHtml(categories[0]?.label || 'Importación')}</span>
        <span><i class="legend-dot legend-export"></i>${escapeHtml(categories[1]?.label || 'Exportación')}</span>
    </div>`;
}

function typeDistribution(distribution, categories) {
    const counts = categories.map(({ value }) => Number(distribution[value] || 0));
    const total = counts.reduce((sum, count) => sum + count, 0);
    const firstPercentage = total ? (counts[0] / total) * 100 : 0;
    const secondPercentage = total ? 100 - firstPercentage : 0;
    const firstLabel = categories[0]?.label || 'Importación';
    const secondLabel = categories[1]?.label || 'Exportación';
    const donutStyle = `--first-share:${firstPercentage}%;`;

    return `<section class="distribution-card">
        <header class="distribution-heading"><h2>Operaciones por tipo</h2><p>Distribución de declaraciones</p></header>
        <div class="donut-wrap">
            <div class="donut-chart ${total ? '' : 'is-empty'}" style="${donutStyle}" role="img" aria-label="${total} declaraciones: ${counts[0] || 0} ${escapeHtml(firstLabel)} y ${counts[1] || 0} ${escapeHtml(secondLabel)}">
                <div class="donut-center"><strong>${total}</strong><span>Total</span></div>
            </div>
        </div>
        <div class="distribution-legend">
            <div><span><i class="legend-dot legend-import"></i>${escapeHtml(firstLabel)}</span><strong>${counts[0] || 0}<small> · ${firstPercentage.toFixed(0)}%</small></strong></div>
            <div><span><i class="legend-dot legend-export"></i>${escapeHtml(secondLabel)}</span><strong>${counts[1] || 0}<small> · ${secondPercentage.toFixed(0)}%</small></strong></div>
        </div>
    </section>`;
}

function recentRows(declarations, typeLabels) {
    if (!declarations.length) {
        return '<tr><td colspan="7"><div class="empty-state"><strong>Aún no hay declaraciones</strong>Las operaciones registradas aparecerán aquí.</div></td></tr>';
    }

    return declarations.slice(0, 5).map((declaration) => {
        const client = declaration.cliente;
        const clientName = client ? `${client.nombres || ''} ${client.apellidos || ''}`.trim() : 'Cliente no disponible';
        const typeLabel = typeLabels.get(declaration.tipo) || declaration.tipo || '—';

        return `<tr data-declaration-row data-search="${escapeHtml(`${declaration.numero_declaracion || ''} ${clientName} ${typeLabel}`.toLowerCase())}">
            <td class="primary-cell">${escapeHtml(declaration.numero_declaracion || `#${declaration.id}`)}</td>
            <td><span class="client-initials">${escapeHtml(clientName.split(/\s+/).map((part) => part[0]).slice(0, 2).join('').toUpperCase() || '—')}</span><span class="recent-client-name">${escapeHtml(clientName)}</span></td>
            <td>${escapeHtml(typeLabel)}</td>
            <td>${formatDate(declaration.fecha_declaracion)}</td>
            <td class="primary-cell">${formatMoney(declaration.valor_mercancia)}</td>
            <td>${statusBadge(declaration.estado)}</td>
            <td><button class="button button-icon" type="button" data-action="declaration-detail" data-id="${escapeHtml(declaration.id)}" aria-label="Ver declaración ${escapeHtml(declaration.numero_declaracion || declaration.id)}">›</button></td>
        </tr>`;
    }).join('');
}

export async function renderDashboard(container) {
    container.innerHTML = '<div class="loading-state">Cargando resumen de operaciones…</div>';
    const [statistics, catalogs] = await Promise.all([
        api.getDashboardStatistics(),
        api.getCatalogos(),
    ]);

    if (!statistics
        || !Array.isArray(statistics.actividad_mensual)
        || !Array.isArray(statistics.declaraciones_recientes)
        || !statistics.distribucion_tipo
        || !Array.isArray(catalogs?.tipos_declaracion)) {
        throw new Error('La API de estadísticas o catálogos respondió con un formato inesperado.');
    }

    const types = catalogs.tipos_declaracion;
    const typeLabels = new Map(types.map(({ value, label }) => [value, label]));
    const categories = types.length >= 2 ? types.slice(0, 2) : [{ value: 'Importacion', label: 'Importación' }, { value: 'Exportacion', label: 'Exportación' }];
    const today = new Date();
    const greeting = today.getHours() < 12 ? 'Buenos días' : today.getHours() < 19 ? 'Buenas tardes' : 'Buenas noches';
    const dateLabel = new Intl.DateTimeFormat('es-EC', { weekday: 'long', day: 'numeric', month: 'long' }).format(today);

    container.innerHTML = `
        <div class="dashboard-greeting">
            <div><p class="eyebrow"><span class="eyebrow-line"></span>${escapeHtml(dateLabel)}</p><h1>${greeting}</h1><p class="page-description">Aquí tienes un resumen de la operación aduanera.</p></div>
            <button class="button button-primary" type="button" data-action="new-declaration"><span>＋</span> Nueva declaración</button>
        </div>
        <section class="stats-grid" aria-label="Estadísticas del sistema">
            <article class="stat-card" style="--stat-tint:#e8f0f8;--stat-color:#367cb7">
                <div class="stat-top"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 1 0 7.75"/></svg></span></div>
                <div class="stat-value">${Number(statistics.clientes_activos)}</div><div class="stat-footnote">Clientes activos</div>
            </article>
            <article class="stat-card" style="--stat-tint:#e5f6f3;--stat-color:#187b78">
                <div class="stat-top"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg></span></div>
                <div class="stat-value">${Number(statistics.declaraciones_mes)}</div><div class="stat-footnote">Declaraciones este mes</div>
            </article>
            <article class="stat-card" style="--stat-tint:#f0eafa;--stat-color:#8059b5">
                <div class="stat-top"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg></span></div>
                <div class="stat-value stat-money">${formatMoney(statistics.pagos_total)}</div><div class="stat-footnote">Pagos registrados</div>
            </article>
            <article class="stat-card" style="--stat-tint:#e5f5ef;--stat-color:#17815e">
                <div class="stat-top"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></span></div>
                <div class="stat-value">${Number(statistics.operaciones_procesadas)}</div><div class="stat-footnote">Operaciones procesadas</div>
            </article>
        </section>
        <div class="dashboard-grid dashboard-charts">
            <section class="panel activity-panel">
                <header class="panel-header"><div><h2>Actividad de declaraciones</h2><p class="panel-subtitle">Importaciones y exportaciones de los últimos 6 meses</p></div><span class="chart-period">Últimos 6 meses <span aria-hidden="true">⌄</span></span></header>
                <div class="activity-chart-wrap">${activityChart(statistics.actividad_mensual, categories)}</div>
            </section>
            ${typeDistribution(statistics.distribucion_tipo, categories)}
        </div>
        <section class="panel recent-panel">
            <header class="panel-header recent-header"><div><h2>Declaraciones recientes</h2><p class="panel-subtitle">Últimas operaciones registradas en el sistema</p></div>
                <div class="recent-actions"><button class="button button-secondary button-small" type="button" data-view="declaraciones">Ver todas&nbsp; →</button></div>
            </header>
            <div class="table-scroll"><table class="data-table recent-table">
                <thead><tr><th>N.º declaración</th><th>Cliente</th><th>Tipo</th><th>Fecha</th><th>Monto</th><th>Estado</th><th aria-label="Acciones"></th></tr></thead>
                <tbody>${recentRows(statistics.declaraciones_recientes, typeLabels)}</tbody>
            </table></div>
        </section>`;

    const searchInput = document.querySelector('#globalSearch');
    if (searchInput?.value.trim()) {
        container.querySelectorAll('[data-declaration-row]').forEach((row) => {
            row.hidden = !row.dataset.search.includes(searchInput.value.trim().toLowerCase());
        });
    }
}
