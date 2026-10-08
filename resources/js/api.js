const API_BASE = '/api';

function setApiAvailability(available) {
    const status = document.querySelector('#apiStatus');
    const label = document.querySelector('#apiStatusLabel');

    if (status && label) {
        status.classList.toggle('is-offline', !available);
        label.textContent = available ? 'Servidor disponible' : 'Sin conexión';
    }
}

function listQuery({ page, perPage = 10, search = '' } = {}) {
    const params = new URLSearchParams({
        page: String(page),
        per_page: String(perPage),
    });

    if (search.trim()) {
        params.set('search', search.trim());
    }

    return `?${params.toString()}`;
}

export class ApiError extends Error {
    constructor(message, { status = 0, errors = {} } = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }
}

async function request(path, options = {}) {
    let response;

    try {
        response = await fetch(`${API_BASE}${path}`, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(document.querySelector('meta[name="csrf-token"]')?.content
                    ? { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    : {}),
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...options.headers,
            },
        });
    } catch {
        setApiAvailability(false);
        throw new ApiError('No fue posible conectar con el servidor. Verifica tu conexión e inténtalo de nuevo.');
    }

    setApiAvailability(true);
    const responseText = await response.text();
    let data = null;

    if (responseText) {
        try {
            data = JSON.parse(responseText);
        } catch {
            if (response.ok) {
                throw new ApiError('El servidor respondió en un formato inesperado.', { status: response.status });
            }
        }
    }

    if (!response.ok) {
        let firstErrorMessage = data?.message;
        if (data?.errors && typeof data.errors === 'object') {
            const firstErrorList = Object.values(data.errors)[0];
            if (Array.isArray(firstErrorList) && firstErrorList.length) {
                firstErrorMessage = firstErrorList[0];
            }
        }

        throw new ApiError(
            firstErrorMessage || `La solicitud no pudo completarse (HTTP ${response.status}).`,
            { status: response.status, errors: data?.errors || {} },
        );
    }

    return data;
}

export const api = {
    getMe: () => request('/me'),
    getHealth: () => request('/health'),
    getCatalogos: () => request('/catalogos'),
    getDashboardStatistics: () => request('/dashboard/estadisticas'),
    getClientes: (options = null) => request(`/clientes${options ? listQuery(options) : ''}`),
    getCliente: (id) => request(`/clientes/${encodeURIComponent(id)}`),
    crearCliente: (cliente) => request('/clientes', {
        method: 'POST',
        body: JSON.stringify(cliente),
    }),
    eliminarCliente: (id) => request(`/clientes/${encodeURIComponent(id)}`, {
        method: 'DELETE',
    }),
    getDeclaraciones: (options = null) => request(`/declaraciones${options ? listQuery(options) : ''}`),
    getSiguienteNumeroDeclaracion: () => request('/declaraciones/siguiente-numero'),
    crearDeclaracionConPago: (registro) => request('/declaraciones/con-pago', {
        method: 'POST',
        body: JSON.stringify(registro),
    }),
    registrarPago: (declaracionId, pago) => request(`/declaraciones/${encodeURIComponent(declaracionId)}/pagos`, {
        method: 'POST',
        body: JSON.stringify(pago),
    }),
    eliminarDeclaracion: (id) => request(`/declaraciones/${encodeURIComponent(id)}`, {
        method: 'DELETE',
    }),
    getPagos: (options = null) => request(`/pagos${options ? listQuery(options) : ''}`),
};

export function errorMessage(error) {
    if (error instanceof ApiError) {
        return error.message;
    }

    return 'Ocurrió un error inesperado. Inténtalo de nuevo.';
}
