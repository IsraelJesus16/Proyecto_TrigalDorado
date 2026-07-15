

const BASE = window.BASE_URL ?? '/dorado/';
const CSRF = window.CSRF_TOKEN ?? '';


export const apiPost = async (endpoint, datos = {}, metodo = 'POST') => {
    const payload = new URLSearchParams({
        ...datos,
        csrf_token: CSRF,
    });

    const respuesta = await fetch(endpoint, {
        method:  metodo,
        credentials: 'same-origin',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body:    payload.toString(),
    });

    if (!respuesta.ok && respuesta.status !== 400 && respuesta.status !== 403 && respuesta.status !== 409) {
        throw new Error(`Error de red: HTTP ${respuesta.status}`);
    }

    const json = await respuesta.json();
    return { status: respuesta.status, ...json };
};


export const verificarSesion = async () => {
    try {
        const resultado = await apiPost(`${BASE}?type=ajax`, { peticion: 'verificar_sesion' });
        return resultado;
    } catch {
        return { autenticado: false };
    }
};


export const verificarMorosidad = async (cedulaCliente) => {
    return apiPost(`${BASE}?page=Pedido&type=ajax`, {
        peticion:        'verificar_morosidad',
        cedula_cliente:  cedulaCliente,
    });
};


export const verificarHistorial = async (cedulaCliente, condicionPago) => {
    return apiPost(`${BASE}?page=Pedido&type=ajax`, {
        peticion:        'verificar_historial',
        cedula_cliente:  cedulaCliente,
        condicion_pago:  condicionPago,
    });
};


export const crearPedido = async (datosPedido) => {
    const { cedulaCliente, condicionPago, metodoPago, descuento, observacion, fechaEntrega, items } = datosPedido;

    return apiPost(`${BASE}?page=Pedido&type=ajax`, {
        peticion:        'crear',
        cedula_cliente:  cedulaCliente || '',
        condicion_pago:  condicionPago,
        metodo_pago:     metodoPago,
        descuento:       descuento ?? 0,
        observacion:     observacion ?? '',
        fecha_entrega:   fechaEntrega ?? '',
        items:           JSON.stringify(items),
    });
};


document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modalesAbiertos = document.querySelectorAll('.admin-modal-overlay.open, .modal.open, .modal-overlay.open');
        modalesAbiertos.forEach(modal => {
            modal.classList.remove('open');
        });
    }
});
