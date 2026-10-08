import { api, errorMessage } from './api.js';
import { closeModal, detailItem, emptyState, escapeHtml, formatDate, formatMoney, fullName, isActive, paginationControls, showConfirmDialog, showModal, statusBadge } from './ui.js';
import { attachFieldValidation, declarationFieldValidator } from './validation.js';

let declarationsCache = [];
let paymentMethods = new Map();
let declarationTypes = new Map();
let declarationRequestId = 0;

function clientDisplayName(client) {
    return fullName(client, client ? `Cliente #${client.id}` : 'Cliente no disponible');
}

function paymentSummary(declaration) {
    const payments = Array.isArray(declaration.pagos) ? declaration.pagos : [];
    const paidPayments = payments.filter((payment) => String(payment.estado).toLowerCase() === 'pagado');

    if (!paidPayments.length) {
        return `<span class="secondary-cell">${payments.length ? 'Sin pago confirmado' : 'Sin pagos'}</span>`;
    }

    const paidTotal = paidPayments.reduce((total, payment) => total + Number(payment.monto || 0), 0);
    const paymentCount = paidPayments.length === 1 ? '1 pago confirmado' : `${paidPayments.length} pagos confirmados`;

    return `<span class="primary-cell">${formatMoney(paidTotal)}</span><span class="secondary-cell">${paymentCount}</span>`;
}

function declarationRows(declarations) {
    if (!declarations.length) {
        return `<tr><td colspan="8">${emptyState('No hay declaraciones registradas', 'Registra una declaración para verla en este listado.')}</td></tr>`;
    }

    return declarations.map((declaration) => {
        const clientName = clientDisplayName(declaration.cliente);
        const typeLabel = declarationTypes.get(declaration.tipo) || declaration.tipo || '—';

        return `<tr data-declaration-row data-search="${escapeHtml(`${declaration.numero_declaracion || ''} ${clientName} ${typeLabel}`.toLowerCase())}">
        <td class="primary-cell">${escapeHtml(declaration.numero_declaracion || `#${declaration.id}`)}</td>
        <td>${escapeHtml(clientName)}</td>
        <td>${escapeHtml(typeLabel)}</td>
        <td>${formatMoney(declaration.valor_mercancia)}</td>
        <td>${paymentSummary(declaration)}</td>
        <td>${statusBadge(declaration.estado)}</td>
        <td>${formatDate(declaration.fecha_declaracion)}</td>
        <td class="actions-cell">
            <button class="button button-icon" type="button" data-action="declaration-detail" data-id="${escapeHtml(declaration.id)}" aria-label="Ver declaración ${escapeHtml(declaration.numero_declaracion || declaration.id)}" title="Ver detalle">⌕</button>
            <button class="button button-icon button-danger-icon" type="button" data-action="delete-declaration" data-id="${escapeHtml(declaration.id)}" data-number="${escapeHtml(declaration.numero_declaracion || declaration.id)}" aria-label="Eliminar declaración ${escapeHtml(declaration.id)}" title="Eliminar declaración">🗑</button>
        </td>
    </tr>`;
    }).join('');
}

export async function renderDeclarations(container, { page = 1, search = document.querySelector('#globalSearch')?.value || '' } = {}) {
    const requestId = ++declarationRequestId;
    container.innerHTML = '<div class="loading-state">Cargando declaraciones…</div>';
    let declarations;
    let catalogs;
    try {
        [declarations, catalogs] = await Promise.all([
            api.getDeclaraciones({ page, search }),
            api.getCatalogos(),
        ]);
    } catch (error) {
        if (requestId === declarationRequestId) {
            throw error;
        }

        return;
    }

    if (requestId !== declarationRequestId) return;

    if (!Array.isArray(declarations?.data)
        || !Number.isInteger(declarations.current_page)
        || !Array.isArray(catalogs?.tipos_declaracion)
        || !Array.isArray(catalogs?.metodos_pago)) {
        throw new Error('La API de declaraciones o catálogos respondió con un formato inesperado.');
    }

    declarationsCache = declarations.data;
    declarationTypes = new Map(catalogs.tipos_declaracion.map(({ value, label }) => [value, label]));
    paymentMethods = new Map(catalogs.metodos_pago.map(({ value, label }) => [value, label]));
    container.innerHTML = `
        <div class="page-heading">
            <div><p class="eyebrow">OPERACIONES</p><h1>Declaraciones</h1><p class="page-description">Consulta las declaraciones y sus clientes asociados.</p></div>
            <button class="button button-primary" type="button" data-action="new-declaration"><span>＋</span> Registrar declaración</button>
        </div>
        <section class="panel">
            <div class="table-toolbar"><span class="table-count"><strong>${declarations.total}</strong> ${declarations.total === 1 ? 'declaración' : 'declaraciones'} registradas</span>
                <span class="table-count">Los pagos y su estado provienen del servidor.</span>
            </div>
            <div class="table-scroll"><table class="data-table">
                <thead><tr><th>Número de declaración</th><th>Cliente</th><th>Tipo</th><th>Valor de mercancía</th><th>Pagos confirmados</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
                <tbody>${declarationRows(declarations.data)}</tbody>
            </table></div>
            ${paginationControls(declarations, 'declarations-page')}
        </section>`;
}

export async function openDeclarationForm() {
    showModal({
        title: 'Registrar declaración y pago',
        subtitle: 'Crea la declaración y registra su pago en una sola operación.',
        body: '<div class="loading-state">Cargando clientes, catálogos y número correlativo…</div>',
    });

    try {
        const [clients, catalogs, siguiente] = await Promise.all([
            api.getClientes(),
            api.getCatalogos(),
            api.getSiguienteNumeroDeclaracion().catch(() => null),
        ]);

        if (!Array.isArray(clients)
            || !Array.isArray(catalogs?.tipos_declaracion)
            || !Array.isArray(catalogs?.metodos_pago)
            || !Array.isArray(catalogs?.estados_pago)) {
            throw new Error('La API de clientes o catálogos respondió con un formato inesperado.');
        }

        const activeClients = clients.filter((client) => isActive(client.activo));
        const catalogsAvailable = catalogs.tipos_declaracion.length && catalogs.metodos_pago.length && catalogs.estados_pago.length;

        if (!activeClients.length || !catalogsAvailable) {
            const noActiveClients = !activeClients.length;
            const message = noActiveClients
                ? (clients.length ? 'Registra un cliente activo para continuar.' : 'Registra el primer cliente antes de crear una declaración.')
                : 'Solicita al administrador que configure los tipos de declaración y métodos de pago.';
            const heading = noActiveClients
                ? (clients.length ? 'No hay clientes activos' : 'No hay clientes registrados')
                : 'Catálogos vacíos';

            showModal({
                title: 'Registrar declaración y pago',
                subtitle: 'Faltan datos configurados en el sistema para continuar.',
                body: `<div class="empty-state"><strong>${heading}</strong>${message}</div>`,
                footer: `<button class="button button-secondary" type="button" data-action="close-modal">Cerrar</button>${noActiveClients ? '<button class="button button-primary" type="button" data-action="new-client">Registrar cliente</button>' : ''}`,
            });
            return;
        }

        const numeroSugerido = siguiente?.data?.numero_declaracion || '';

        showModal({
            title: 'Registrar declaración y pago',
            subtitle: 'Crea la declaración y registra su pago en una sola operación.',
            body: `<form id="declarationForm" novalidate>
                <div class="form-error-summary" data-form-errors hidden></div>
                <div class="form-grid">
                    <div class="form-field full-width"><label for="cliente_id">Cliente <span>*</span></label>
                        <select class="form-control" id="cliente_id" name="cliente_id" data-validate required>
                            <option value="">Selecciona un cliente</option>
                            ${activeClients.map((client) => `<option value="${escapeHtml(client.id)}">${escapeHtml(fullName(client, `Cliente #${client.id}`))}</option>`).join('')}
                        </select><small class="field-error" id="error-cliente_id" data-error-for="cliente_id" hidden></small>
                    </div>
                    <div class="form-field"><label for="numero_declaracion">Número de declaración <span>*</span></label><input class="form-control" id="numero_declaracion" name="numero_declaracion" value="${escapeHtml(numeroSugerido)}" data-validate minlength="6" maxlength="20" placeholder="DA-001" required><small class="form-hint">Autogenerado secuencialmente.</small><small class="field-error" id="error-numero_declaracion" data-error-for="numero_declaracion" hidden></small></div>
                    <div class="form-field"><label for="tipo">Tipo <span>*</span></label><select class="form-control" id="tipo" name="tipo" data-validate required>
                        <option value="">Selecciona el tipo</option>${catalogs.tipos_declaracion.map(({ value, label }) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`).join('')}
                    </select><small class="field-error" id="error-tipo" data-error-for="tipo" hidden></small></div>
                    <div class="form-field"><label for="valor_mercancia">Valor de mercancía (USD) <span>*</span></label><input class="form-control" id="valor_mercancia" name="valor_mercancia" data-validate type="text" maxlength="32" inputmode="decimal" placeholder="1500.00" required><small class="field-error" id="error-valor_mercancia" data-error-for="valor_mercancia" hidden></small></div>
                    <div class="form-field"><label for="monto">Monto del pago (USD) <span>*</span></label><input class="form-control" id="monto" name="monto" data-validate type="text" maxlength="32" inputmode="decimal" placeholder="1500.00" required><small class="field-error" id="error-monto" data-error-for="monto" hidden></small></div>
                    <div class="form-field"><label for="metodo_pago">Método de pago <span>*</span></label><select class="form-control" id="metodo_pago" name="metodo_pago" data-validate required>
                        <option value="">Selecciona un método</option>${catalogs.metodos_pago.map(({ value, label }) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`).join('')}
                    </select><small class="field-error" id="error-metodo_pago" data-error-for="metodo_pago" hidden></small></div>
                    <div class="form-field"><label for="estado">Estado del pago <span>*</span></label><select class="form-control" id="estado" name="estado" data-validate required>
                        <option value="">Selecciona un estado</option>${catalogs.estados_pago.map(({ value, label }) => `<option value="${escapeHtml(value)}" ${value === 'pagado' ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                    </select><small class="field-error" id="error-estado" data-error-for="estado" hidden></small></div>
                </div>
            </form>`,
            footer: '<button class="button button-secondary" type="button" data-action="close-modal">Cancelar</button><button class="button button-primary" type="submit" form="declarationForm" data-submit-button>Registrar operación</button>',
        });
        const form = document.querySelector('#declarationForm');
        const options = {
            tipos: new Set(catalogs.tipos_declaracion.map(({ value }) => value)),
            metodos: new Set(catalogs.metodos_pago.map(({ value }) => value)),
            estados: new Set(catalogs.estados_pago.map(({ value }) => value)),
        };
        form.validation = attachFieldValidation(form, declarationFieldValidator(options));
    } catch (error) {
        showModal({
            title: 'No se pudieron cargar los datos',
            body: `<div class="error-box">${escapeHtml(errorMessage(error))}</div>`,
            footer: '<button class="button button-secondary" type="button" data-action="close-modal">Cerrar</button>',
        });
    }
}

export async function showDeclarationDetail(id) {
    if (!declarationsCache.some((item) => String(item.id) === String(id))) {
        declarationsCache = await api.getDeclaraciones();
    }

    const declaration = declarationsCache.find((item) => String(item.id) === String(id));

    if (!declaration) {
        throw new Error('No se encontró la declaración en la lista actual. Actualiza la página e inténtalo de nuevo.');
    }

    const payments = Array.isArray(declaration.pagos) ? declaration.pagos : [];
    const isPending = declaration.estado === 'pendiente';

    showModal({
        title: 'Detalle de la declaración',
        subtitle: declaration.numero_declaracion || `Declaración #${declaration.id}`,
        narrow: false,
        body: `<dl class="details-list">
            ${detailItem('Número', escapeHtml(declaration.numero_declaracion || '—'))}
            ${detailItem('Estado', statusBadge(declaration.estado))}
            ${detailItem('Cliente', escapeHtml(clientDisplayName(declaration.cliente)))}
            ${detailItem('Tipo', escapeHtml(declarationTypes.get(declaration.tipo) || declaration.tipo || '—'))}
            ${detailItem('Valor de mercancía', formatMoney(declaration.valor_mercancia))}
            ${detailItem('Fecha', formatDate(declaration.fecha_declaracion))}
        </dl>
        <h3 class="payment-heading">Pagos registrados</h3>
        ${paymentDetails(payments)}`,
        footer: `
            <button class="button button-secondary" type="button" data-action="close-modal">Cerrar</button>
            ${isPending ? `<button class="button button-primary" type="button" data-action="add-payment-modal" data-id="${escapeHtml(declaration.id)}">Registrar abono/pago</button>` : ''}
        `,
    });
}

function paymentDetails(payments) {
    if (!payments.length) {
        return '<div class="empty-state">Esta declaración no tiene pagos registrados.</div>';
    }

    return `<div class="table-scroll"><table class="data-table payment-table">
        <thead><tr><th>Monto</th><th>Método</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody>${payments.map((payment) => `<tr>
            <td class="primary-cell">${formatMoney(payment.monto)}</td>
            <td>${escapeHtml(paymentMethods.get(payment.metodo_pago) || payment.metodo_pago || '—')}</td>
            <td>${statusBadge(payment.estado)}</td>
            <td>${formatDate(payment.fecha_pago)}</td>
        </tr>`).join('')}</tbody>
    </table></div>`;
}

export function confirmDeleteDeclaration(id, number, { notify, onSuccess }) {
    showConfirmDialog({
        title: '¿Eliminar declaración?',
        message: `¿Estás seguro de que deseas eliminar la declaración "${number}"? Esta acción marcará la declaración como eliminada de forma segura.`,
        confirmText: 'Sí, eliminar declaración',
        onConfirm: async () => {
            try {
                await api.eliminarDeclaracion(id);
                notify('Declaración eliminada', 'La declaración ha sido eliminada correctamente.');
                await onSuccess();
            } catch (error) {
                notify('No se pudo eliminar la declaración', errorMessage(error), true);
            }
        },
    });
}

export async function submitDeclarationForm(form, { notify, onSuccess }) {
    const errorSummary = form.querySelector('[data-form-errors]');
    errorSummary.hidden = true;
    errorSummary.textContent = '';

    if (!form.validation.validateAll()) {
        return;
    }

    const data = new FormData(form);
    const registration = {
        cliente_id: Number(data.get('cliente_id')),
        numero_declaracion: String(data.get('numero_declaracion')),
        tipo: String(data.get('tipo')),
        valor_mercancia: String(data.get('valor_mercancia')),
        monto: String(data.get('monto')),
        metodo_pago: String(data.get('metodo_pago')),
        estado: String(data.get('estado')),
    };

    const submitButton = form.closest('.modal').querySelector('[data-submit-button]');
    submitButton.disabled = true;
    submitButton.textContent = 'Registrando…';

    let result;
    try {
        result = await api.crearDeclaracionConPago(registration);
    } catch (error) {
        if (Object.keys(error?.errors || {}).length) {
            form.validation.showServerErrors(error.errors);
        } else {
            form.validation.showSummary(errorMessage(error));
        }
        submitButton.disabled = false;
        submitButton.textContent = 'Registrar operación';
        return;
    }

    closeModal();
    const paymentText = result?.pago
        ? ` Pago de ${formatMoney(result.pago.monto)} registrado como ${result.pago.estado || 'pagado'}.`
        : '';
    notify('Operación registrada', `${result?.message || 'La declaración se registró correctamente.'}${paymentText}`);
    await onSuccess();
}
