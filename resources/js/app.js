import { renderDashboard } from './dashboard.js';
import { confirmDeleteClient, openClientForm, renderClients, showClientDetail, submitClientForm } from './clientes.js';
import { confirmDeleteDeclaration, openDeclarationForm, renderDeclarations, showDeclarationDetail, submitDeclarationForm } from './declaraciones.js';
import { renderPagos } from './pagos.js';
import { renderConfiguracion } from './configuracion.js';
import { api, errorMessage } from './api.js';
import { closeModal, loadError, showModal } from './ui.js';

const content = document.querySelector('#pageContent');
const viewTitles = {
    dashboard: 'Dashboard',
    clientes: 'Clientes',
    declaraciones: 'Declaraciones',
    pagos: 'Pagos',
    configuracion: 'Configuración',
};
const views = {
    dashboard: renderDashboard,
    clientes: renderClients,
    declaraciones: renderDeclarations,
    pagos: renderPagos,
    configuracion: renderConfiguracion,
};
let currentView = 'dashboard';

function notify(title, message, isError = false) {
    const region = document.querySelector('#toastRegion');
    const toast = document.createElement('div');
    toast.className = `toast ${isError ? 'is-error' : ''}`;
    const icon = document.createElement('span');
    icon.className = 'toast-icon';
    icon.textContent = isError ? '!' : '✓';
    const content = document.createElement('span');
    content.className = 'toast-content';
    const heading = document.createElement('strong');
    heading.textContent = title;
    const description = document.createElement('span');
    description.textContent = message;
    content.append(heading, description);
    toast.append(icon, content);
    region.append(toast);
    window.setTimeout(() => toast.remove(), 5200);
}

async function navigate(view) {
    if (!views[view]) {
        return;
    }

    currentView = view;
    document.querySelectorAll('.nav-link').forEach((link) => {
        const active = link.dataset.view === view;
        link.classList.toggle('is-active', active);
        if (active) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
        }
    });
    document.querySelector('#breadcrumbCurrent').textContent = viewTitles[view];
    document.querySelector('#sidebar').classList.remove('is-open');
    content.innerHTML = '<div class="loading-state">Cargando…</div>';

    try {
        await views[view](content);
    } catch (error) {
        content.innerHTML = loadError(errorMessage(error));
    }
}

function showViewError(error) {
    content.innerHTML = loadError(errorMessage(error));
}

function refreshCurrentView() {
    return navigate(currentView);
}

async function openPaymentModal(declaracionId) {
    const catalogs = await api.getCatalogos();
    const metodos = catalogs?.metodos_pago || [];
    const estados = catalogs?.estados_pago || [];

    showModal({
        title: 'Registrar abono/pago',
        subtitle: 'Añadir un pago a esta declaración existente',
        narrow: true,
        body: `<form id="paymentForm" data-declaracion-id="${escapeHtml(declaracionId)}" novalidate>
            <div class="form-error-summary" data-form-errors hidden></div>
            <div class="form-grid">
                <div class="form-field full-width"><label for="monto">Monto a abonar (USD) <span>*</span></label><input class="form-control" id="monto" name="monto" type="text" inputmode="decimal" placeholder="500.00" required><small class="field-error" id="error-monto" data-error-for="monto" hidden></small></div>
                <div class="form-field full-width"><label for="metodo_pago">Método de pago <span>*</span></label><select class="form-control" id="metodo_pago" name="metodo_pago" required>
                    <option value="">Selecciona método</option>${metodos.map(({ value, label }) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`).join('')}
                </select></div>
                <div class="form-field full-width"><label for="estado">Estado del pago <span>*</span></label><select class="form-control" id="estado" name="estado" required>
                    ${estados.map(({ value, label }) => `<option value="${escapeHtml(value)}" ${value === 'pagado' ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                </select></div>
            </div>
        </form>`,
        footer: '<button class="button button-secondary" type="button" data-action="close-modal">Cancelar</button><button class="button button-primary" type="submit" form="paymentForm" data-submit-button>Guardar pago</button>',
    });
}

document.addEventListener('click', async (event) => {
    const viewTarget = event.target.closest('[data-view]');
    if (viewTarget) {
        event.preventDefault();
        await navigate(viewTarget.dataset.view);
        return;
    }

    const actionTarget = event.target.closest('[data-action]');
    if (!actionTarget) {
        if (event.target.matches('[data-modal-backdrop]')) {
            closeModal();
        }
        return;
    }

    switch (actionTarget.dataset.action) {
        case 'new-client':
            openClientForm();
            break;
        case 'delete-client':
            confirmDeleteClient(actionTarget.dataset.id, actionTarget.dataset.name, {
                notify,
                onSuccess: refreshCurrentView,
            });
            break;
        case 'new-declaration':
            await openDeclarationForm();
            break;
        case 'delete-declaration':
            confirmDeleteDeclaration(actionTarget.dataset.id, actionTarget.dataset.number, {
                notify,
                onSuccess: refreshCurrentView,
            });
            break;
        case 'add-payment-modal':
            await openPaymentModal(actionTarget.dataset.id);
            break;
        case 'client-detail':
            try {
                await showClientDetail(actionTarget.dataset.id);
            } catch (error) {
                notify('No se pudo cargar el cliente', errorMessage(error), true);
            }
            break;
        case 'declaration-detail':
            try {
                await showDeclarationDetail(actionTarget.dataset.id);
            } catch (error) {
                notify('No se pudo cargar la declaración', errorMessage(error), true);
            }
            break;
        case 'close-modal':
            closeModal();
            break;
        case 'retry-view':
            await refreshCurrentView();
            break;
        case 'clients-page':
            await renderClients(content, {
                page: Number(actionTarget.dataset.page),
                search: content.querySelector('[data-client-search]')?.value || '',
            }).catch(showViewError);
            break;
        case 'declarations-page':
            await renderDeclarations(content, {
                page: Number(actionTarget.dataset.page),
                search: globalSearch.value,
            }).catch(showViewError);
            break;
        case 'pagos-page':
            await renderPagos(content, {
                page: Number(actionTarget.dataset.page),
                search: content.querySelector('[data-pago-search]')?.value || '',
            }).catch(showViewError);
            break;
        default:
            break;
    }
});

document.addEventListener('submit', async (event) => {
    if (event.target.id === 'clientForm') {
        event.preventDefault();
        await submitClientForm(event.target, { notify, onSuccess: refreshCurrentView });
    } else if (event.target.id === 'declarationForm') {
        event.preventDefault();
        await submitDeclarationForm(event.target, { notify, onSuccess: refreshCurrentView });
    } else if (event.target.id === 'paymentForm') {
        event.preventDefault();
        const form = event.target;
        const declaracionId = form.dataset.declaracionId;
        const formData = new FormData(form);
        const submitBtn = form.closest('.modal').querySelector('[data-submit-button]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Guardando…';

        try {
            await api.registrarPago(declaracionId, {
                monto: formData.get('monto'),
                metodo_pago: formData.get('metodo_pago'),
                estado: formData.get('estado'),
            });
            closeModal();
            notify('Pago registrado', 'El abono/pago se guardó correctamente.');
            await refreshCurrentView();
        } catch (error) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Guardar pago';
            notify('No se pudo registrar el pago', errorMessage(error), true);
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeModal();
        document.querySelector('#sidebar').classList.remove('is-open');
    }
});

document.querySelector('#menuToggle').addEventListener('click', () => {
    document.querySelector('#sidebar').classList.toggle('is-open');
});

const globalSearch = document.querySelector('#globalSearch');
let declarationSearchTimer;

globalSearch.addEventListener('input', () => {
    if (currentView === 'dashboard') {
        const query = globalSearch.value.trim().toLowerCase();
        content.querySelectorAll('[data-declaration-row]').forEach((row) => {
            row.hidden = !row.dataset.search.includes(query);
        });
        return;
    }

    if (currentView !== 'declaraciones') return;

    window.clearTimeout(declarationSearchTimer);
    declarationSearchTimer = window.setTimeout(() => {
        renderDeclarations(content, { page: 1, search: globalSearch.value }).catch(showViewError);
    }, 250);
});

document.querySelector('#globalSearchForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    if (globalSearch.value.trim() && currentView !== 'declaraciones') {
        await navigate('declaraciones');
    }
});

document.querySelector('#currentDate').textContent = new Intl.DateTimeFormat('es-EC', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
}).format(new Date());

navigate('dashboard');
