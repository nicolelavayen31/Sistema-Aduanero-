import { api, errorMessage } from './api.js';
import { emptyState, escapeHtml, formatDate, formatMoney, fullName, loadError, paginationControls, statusBadge } from './ui.js';

let pagosRequestId = 0;

function pagoRows(pagos) {
    if (!pagos.length) {
        return `<tr><td colspan="7">${emptyState('No hay pagos registrados', 'Los pagos registrados en las declaraciones aparecerán aquí.')}</td></tr>`;
    }

    return pagos.map((pago) => {
        const declaracion = pago.declaracion;
        const cliente = declaracion?.cliente;
        const clientName = fullName(cliente, 'Cliente no disponible');
        const numDeclaracion = declaracion?.numero_declaracion || `Declaración #${pago.declaracion_id}`;

        return `<tr data-pago-row>
            <td class="primary-cell">#${escapeHtml(pago.id)}</td>
            <td class="primary-cell">${formatMoney(pago.monto)}</td>
            <td>${escapeHtml(pago.metodo_pago || '—')}</td>
            <td><strong>${escapeHtml(numDeclaracion)}</strong></td>
            <td>${escapeHtml(clientName)}</td>
            <td>${statusBadge(pago.estado)}</td>
            <td>${formatDate(pago.fecha_pago || pago.created_at)}</td>
        </tr>`;
    }).join('');
}

export async function renderPagos(container, { page = 1, search = '' } = {}) {
    const requestId = ++pagosRequestId;
    container.innerHTML = '<div class="loading-state">Cargando historial de pagos…</div>';

    let result;
    try {
        result = await api.getPagos({ page, search });
    } catch (error) {
        if (requestId === pagosRequestId) {
            throw error;
        }

        return;
    }

    if (requestId !== pagosRequestId) return;
    const pagos = result?.data;

    if (!Array.isArray(pagos) || !Number.isInteger(result.current_page)) {
        throw new Error('La API de pagos respondió con un formato inesperado.');
    }

    container.innerHTML = `
        <div class="page-heading">
            <div><p class="eyebrow">GESTIÓN FINANCIERA</p><h1>Pagos</h1><p class="page-description">Consulta el historial de pagos y abonos registrados en las declaraciones aduaneras.</p></div>
        </div>
        <section class="panel">
            <div class="table-toolbar">
                <span class="table-count"><strong>${result.total}</strong> ${result.total === 1 ? 'pago' : 'pagos'} registrados</span>
                <label class="search-wrap">
                    <span class="visually-hidden">Buscar pagos</span>
                    <input class="search-input" type="search" value="${escapeHtml(search)}" placeholder="Buscar por declaración, cliente o método…" data-pago-search>
                </label>
            </div>
            <div class="table-scroll"><table class="data-table">
                <thead><tr><th>ID</th><th>Monto</th><th>Método</th><th>Declaración</th><th>Cliente</th><th>Estado</th><th>Fecha</th></tr></thead>
                <tbody>${pagoRows(pagos)}</tbody>
            </table></div>
            ${paginationControls(result, 'pagos-page')}
        </section>`;

    let searchTimer;
    container.querySelector('[data-pago-search]')?.addEventListener('input', (event) => {
        const query = event.currentTarget.value;
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
            renderPagos(container, { page: 1, search: query }).catch((error) => {
                container.innerHTML = loadError(errorMessage(error));
            });
        }, 250);
    });
}
