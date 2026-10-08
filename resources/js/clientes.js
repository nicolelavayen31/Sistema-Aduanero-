import { api, errorMessage } from './api.js';
import { closeModal, detailItem, emptyState, escapeHtml, fullName, isActive, loadError, paginationControls, showConfirmDialog, showModal, statusBadge } from './ui.js';
import { attachFieldValidation, clientFieldValidator } from './validation.js';

let clientRequestId = 0;

function clientRows(clients) {
    if (!clients.length) {
        return `<tr><td colspan="8">${emptyState('No hay clientes registrados', 'Agrega el primer cliente para comenzar.')}</td></tr>`;
    }

    return clients.map((client) => `<tr data-client-row data-search="${escapeHtml(`${client.id} ${fullName(client)} ${client.cedula || ''} ${client.email || ''}`.toLowerCase())}">
        <td>${escapeHtml(client.id)}</td>
        <td class="primary-cell">${escapeHtml(client.nombres || '—')}</td>
        <td>${escapeHtml(client.apellidos || '—')}</td>
        <td>${escapeHtml(client.cedula || '—')}</td>
        <td>${escapeHtml(client.email || '—')}</td>
        <td>${escapeHtml(client.telefono || '—')}</td>
        <td>${statusBadge(isActive(client.activo) ? 'activo' : 'inactivo')}</td>
        <td class="actions-cell">
            <button class="button button-icon" type="button" data-action="client-detail" data-id="${escapeHtml(client.id)}" aria-label="Ver cliente ${escapeHtml(client.id)}" title="Ver detalle">⌕</button>
            <button class="button button-icon button-danger-icon" type="button" data-action="delete-client" data-id="${escapeHtml(client.id)}" data-name="${escapeHtml(fullName(client))}" aria-label="Eliminar cliente ${escapeHtml(client.id)}" title="Eliminar cliente">🗑</button>
        </td>
    </tr>`).join('');
}

export async function renderClients(container, { page = 1, search = '' } = {}) {
    const requestId = ++clientRequestId;
    const searchInput = container.querySelector('[data-client-search]');
    const preserveSearchFocus = searchInput && document.activeElement === searchInput;
    const selectionStart = preserveSearchFocus ? searchInput.selectionStart : null;

    if (!preserveSearchFocus) {
        container.innerHTML = '<div class="loading-state">Cargando clientes…</div>';
    }

    let result;
    try {
        result = await api.getClientes({ page, search });
    } catch (error) {
        if (requestId === clientRequestId) {
            throw error;
        }

        return;
    }

    if (requestId !== clientRequestId) return;
    const clients = result?.data;

    if (!Array.isArray(clients) || !Number.isInteger(result.current_page) || !Number.isInteger(result.last_page)) {
        throw new Error('La API de clientes respondió con un formato paginado inesperado.');
    }

    container.innerHTML = `
        <div class="page-heading">
            <div><p class="eyebrow">DIRECTORIO</p><h1>Clientes</h1><p class="page-description">Consulta y administra los clientes registrados.</p></div>
            <button class="button button-primary" type="button" data-action="new-client"><span>＋</span> Nuevo cliente</button>
        </div>
        <section class="panel">
            <div class="table-toolbar"><span class="table-count"><strong>${result.total}</strong> ${result.total === 1 ? 'cliente' : 'clientes'} registrados</span>
                <label class="search-wrap"><span class="visually-hidden">Buscar clientes</span><input class="search-input" type="search" value="${escapeHtml(search)}" placeholder="Buscar por nombre, cédula o email…" data-client-search></label>
            </div>
            <div class="table-scroll"><table class="data-table">
                <thead><tr><th>ID</th><th>Nombres</th><th>Apellidos</th><th>Cédula</th><th>Email</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>${clientRows(clients)}</tbody>
            </table></div>
            ${paginationControls(result, 'clients-page')}
        </section>`;

    if (preserveSearchFocus) {
        const nextSearchInput = container.querySelector('[data-client-search]');
        nextSearchInput?.focus();
        if (selectionStart !== null) {
            nextSearchInput?.setSelectionRange(selectionStart, selectionStart);
        }
    }

    let searchTimer;
    container.querySelector('[data-client-search]')?.addEventListener('input', (event) => {
        const query = event.currentTarget.value;
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
            renderClients(container, { page: 1, search: query }).catch((error) => {
                container.innerHTML = loadError(errorMessage(error));
            });
        }, 250);
    });
}

export function openClientForm() {
    showModal({
        title: 'Nuevo cliente',
        subtitle: 'Completa los datos para registrar un cliente.',
        body: `<form id="clientForm" novalidate>
            <div class="form-error-summary" data-form-errors hidden></div>
            <div class="form-grid">
                <div class="form-field"><label for="nombres">Nombres <span>*</span></label><input class="form-control" id="nombres" name="nombres" data-validate maxlength="512" autocomplete="given-name" required><small class="field-error" id="error-nombres" data-error-for="nombres" hidden></small></div>
                <div class="form-field"><label for="apellidos">Apellidos <span>*</span></label><input class="form-control" id="apellidos" name="apellidos" data-validate maxlength="512" autocomplete="family-name" required><small class="field-error" id="error-apellidos" data-error-for="apellidos" hidden></small></div>
                <div class="form-field"><label for="cedula">Cédula <span>*</span></label><input class="form-control" id="cedula" name="cedula" data-validate type="text" inputmode="numeric" minlength="10" maxlength="32" pattern="[0-9]{10}" autocomplete="off" required><small class="form-hint">10 números y dígito verificador ecuatoriano.</small><small class="field-error" id="error-cedula" data-error-for="cedula" hidden></small></div>
                <div class="form-field"><label for="email">Email <span>*</span></label><input class="form-control" id="email" name="email" data-validate type="text" inputmode="email" maxlength="512" autocomplete="email" required><small class="field-error" id="error-email" data-error-for="email" hidden></small></div>
                <div class="form-field"><label for="telefono">Teléfono</label><input class="form-control" id="telefono" name="telefono" data-validate type="text" inputmode="numeric" minlength="10" maxlength="32" pattern="[0-9]{10}" autocomplete="tel-national"><small class="form-hint">Si lo ingresas, debe tener 10 números.</small><small class="field-error" id="error-telefono" data-error-for="telefono" hidden></small></div>
                <div class="form-field"><label for="activo">Estado</label><label class="checkbox-field"><input id="activo" name="activo" type="checkbox" checked> Cliente activo</label></div>
                <div class="form-field full-width"><label for="direccion">Dirección</label><textarea class="form-control" id="direccion" name="direccion" data-validate maxlength="512" autocomplete="street-address"></textarea><small class="field-error" id="error-direccion" data-error-for="direccion" hidden></small></div>
            </div>
        </form>`,
        footer: '<button class="button button-secondary" type="button" data-action="close-modal">Cancelar</button><button class="button button-primary" type="submit" form="clientForm" data-submit-button>Guardar cliente</button>',
    });
    document.querySelector('#clientForm').validation = attachFieldValidation(
        document.querySelector('#clientForm'),
        clientFieldValidator,
        { normalizeNames: true },
    );
}

export async function showClientDetail(id) {
    const client = await api.getCliente(id);
    showModal({
        title: 'Detalle del cliente',
        subtitle: fullName(client, `Cliente #${client.id}`),
        narrow: true,
        body: `<dl class="details-list">
            ${detailItem('ID', escapeHtml(client.id))}
            ${detailItem('Estado', statusBadge(isActive(client.activo) ? 'activo' : 'inactivo'))}
            ${detailItem('Nombres', escapeHtml(client.nombres || '—'))}
            ${detailItem('Apellidos', escapeHtml(client.apellidos || '—'))}
            ${detailItem('Cédula', escapeHtml(client.cedula || '—'))}
            ${detailItem('Email', escapeHtml(client.email || '—'))}
            ${detailItem('Teléfono', escapeHtml(client.telefono || '—'))}
            ${detailItem('Dirección', escapeHtml(client.direccion || '—'))}
        </dl>`,
        footer: '<button class="button button-secondary" type="button" data-action="close-modal">Cerrar</button>',
    });
}

export function confirmDeleteClient(id, name, { notify, onSuccess }) {
    showConfirmDialog({
        title: '¿Eliminar cliente?',
        message: `¿Estás seguro de que deseas eliminar el cliente "${name}"? Si el cliente tiene declaraciones asociadas, el sistema protegerá la información impidiendo su borrado.`,
        confirmText: 'Sí, eliminar cliente',
        onConfirm: async () => {
            try {
                await api.eliminarCliente(id);
                notify('Cliente eliminado', 'El cliente ha sido eliminado de forma segura.');
                await onSuccess();
            } catch (error) {
                notify('No se pudo eliminar el cliente', errorMessage(error), true);
            }
        },
    });
}

export async function submitClientForm(form, { notify, onSuccess }) {
    const errorSummary = form.querySelector('[data-form-errors]');
    errorSummary.hidden = true;
    errorSummary.textContent = '';

    if (!form.validation.validateAll()) {
        return;
    }

    const formData = new FormData(form);
    const client = {
        nombres: String(formData.get('nombres')).trim(),
        apellidos: String(formData.get('apellidos')).trim(),
        cedula: String(formData.get('cedula')),
        email: String(formData.get('email')),
        telefono: String(formData.get('telefono') || '') || null,
        direccion: String(formData.get('direccion') || '').trim() || null,
        activo: form.querySelector('[name="activo"]').checked,
    };

    const submitButton = form.closest('.modal').querySelector('[data-submit-button]');
    submitButton.disabled = true;
    submitButton.textContent = 'Guardando…';

    try {
        await api.crearCliente(client);
    } catch (error) {
        if (Object.keys(error?.errors || {}).length) {
            form.validation.showServerErrors(error.errors);
        } else {
            form.validation.showSummary(errorMessage(error));
        }
        submitButton.disabled = false;
        submitButton.textContent = 'Guardar cliente';
        return;
    }

    closeModal();
    notify('Cliente registrado', 'El cliente se guardó correctamente.');
    await onSuccess();
}
