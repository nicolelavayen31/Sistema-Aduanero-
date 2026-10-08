export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);
}

export function formatMoney(value) {
    const amount = Number(value);

    if (!Number.isFinite(amount)) {
        return '—';
    }

    return new Intl.NumberFormat('es-EC', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}

export function formatDate(value) {
    if (!value) {
        return '—';
    }

    const raw = String(value).slice(0, 10);
    const parts = raw.split('-').map(Number);

    let date;
    if (parts.length === 3 && !parts.some(Number.isNaN)) {
        date = new Date(parts[0], parts[1] - 1, parts[2]);
    } else {
        date = new Date(value);
    }

    if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
    }

    return new Intl.DateTimeFormat('es-EC', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

export function isActive(value) {
    return value === true || value === 1 || value === '1';
}

export function fullName(person, fallback = '') {
    const name = `${person?.nombres || ''} ${person?.apellidos || ''}`.trim();
    return name || fallback;
}

export function statusBadge(value) {
    const status = String(value ?? '').trim().toLowerCase();
    const labels = {
        activo: 'Activo',
        inactive: 'Inactivo',
        inactivo: 'Inactivo',
        pendiente: 'Pendiente',
        pagado: 'Pagado',
        paid: 'Pagado',
        completado: 'Completado',
        cancelado: 'Cancelado',
        cancelled: 'Cancelado',
        rechazado: 'Rechazado',
    };
    let className = 'is-neutral';

    if (['activo', 'active'].includes(status)) {
        className = 'is-active';
    } else if (['pagado', 'paid', 'completado', 'completed'].includes(status)) {
        className = 'is-paid';
    } else if (['pendiente', 'pending'].includes(status)) {
        className = 'is-pending';
    } else if (['inactivo', 'inactive', 'cancelado', 'cancelled', 'rechazado'].includes(status)) {
        className = 'is-inactive';
    }

    const label = labels[status] || (value ? String(value) : 'Sin estado');
    return `<span class="status-badge ${className}">${escapeHtml(label)}</span>`;
}

export function showModal({ title, subtitle = '', body, footer = '', narrow = false }) {
    const root = document.querySelector('#modalRoot');

    root.innerHTML = `
        <div class="modal-backdrop" data-modal-backdrop>
            <section class="modal ${narrow ? 'modal-narrow' : ''}" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                <header class="modal-header">
                    <div><h2 id="modalTitle">${escapeHtml(title)}</h2>${subtitle ? `<p>${escapeHtml(subtitle)}</p>` : ''}</div>
                    <button class="modal-close" type="button" data-action="close-modal" aria-label="Cerrar">×</button>
                </header>
                <div class="modal-body">${body}</div>
                ${footer ? `<footer class="modal-footer">${footer}</footer>` : ''}
            </section>
        </div>`;

    root.querySelector('.modal-close')?.focus();
}

export function showConfirmDialog({
    title = '¿Confirmar acción?',
    message = 'Esta acción no se puede deshacer.',
    confirmText = 'Sí, continuar',
    cancelText = 'Cancelar',
    onConfirm,
}) {
    showModal({
        title,
        subtitle: 'Atención: Confirmación requerida',
        narrow: true,
        body: `<div class="confirm-dialog-content"><p>${escapeHtml(message)}</p></div>`,
        footer: `
            <button class="button button-secondary" type="button" data-action="close-modal">${escapeHtml(cancelText)}</button>
            <button class="button button-danger" type="button" id="btnConfirmAction">${escapeHtml(confirmText)}</button>
        `,
    });

    document.querySelector('#btnConfirmAction')?.addEventListener('click', async () => {
        closeModal();
        if (typeof onConfirm === 'function') {
            await onConfirm();
        }
    });
}

export function closeModal() {
    document.querySelector('#modalRoot').innerHTML = '';
}

export function detailItem(label, value) {
    return `<div class="detail-item"><dt>${escapeHtml(label)}</dt><dd>${value}</dd></div>`;
}

export function emptyState(title, message) {
    return `<div class="empty-state"><strong>${escapeHtml(title)}</strong>${escapeHtml(message)}</div>`;
}

export function loadError(message) {
    return `<div class="error-box" role="alert">${escapeHtml(message)}<button type="button" data-action="retry-view">Intentar de nuevo</button></div>`;
}

export function paginationControls(pagination, action) {
    const { current_page: currentPage, last_page: lastPage, from, to, total } = pagination;

    return `<footer class="pagination-bar">
        <span>Mostrando ${from || 0}–${to || 0} de ${total}</span>
        <div>
            <button class="button button-secondary button-small" type="button" data-action="${action}" data-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''}>Anterior</button>
            <span>Página ${currentPage} de ${lastPage}</span>
            <button class="button button-secondary button-small" type="button" data-action="${action}" data-page="${currentPage + 1}" ${currentPage >= lastPage ? 'disabled' : ''}>Siguiente</button>
        </div>
    </footer>`;
}
