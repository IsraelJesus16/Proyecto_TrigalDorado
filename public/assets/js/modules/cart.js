/**
 * cart.js — Módulo del carrito de compras con interceptor de autenticación
 * El Trigal Dorado TPS · Vanilla JS ES6+ Modules
 *
 * Flujo:
 *   1. Usuario hace clic en "Añadir al carrito"
 *   2. e.preventDefault() detiene la acción predeterminada
 *   3. Se verifica sesión via fetch al backend
 *   4a. Si hay sesión → añadir al carrito (estado interno)
 *   4b. Si no hay sesión → mostrar modal de login (sin redirección)
 *   5. Tras login exitoso → el producto se añade automáticamente
 */

import { verificarSesion } from './api.js';

// ─── Estado del carrito (función pura, sin variables globales mutables) ─
let estadoCarrito = {
    items: [],
    visible: false,
};

// Elemento pendiente de añadir (mientras espera auth)
let productosPendientes = null;

// ─── Inicialización ────────────────────────────────────────────────────
export const initCarrito = () => {
    // Interceptar todos los botones de "Añadir al carrito"
    document.addEventListener('click', async (e) => {
        const btnCarrito = e.target.closest('[data-accion="agregar-carrito"]');
        if (!btnCarrito) return;

        e.preventDefault();
        e.stopPropagation();

        const producto = {
            id:           btnCarrito.dataset.id,
            nombre:       btnCarrito.dataset.nombre,
            precio:       parseFloat(btnCarrito.dataset.precio),
            imagen:       btnCarrito.dataset.imagen,
            cantidad:     1,
        };

        await manejarAgregarProducto(producto, btnCarrito);
    });

    // Botón del carrito (drawer)
    document.querySelector('#btn-abrir-carrito')?.addEventListener('click', toggleCarrito);

    // Cerrar carrito
    document.querySelector('#btn-cerrar-carrito')?.addEventListener('click', cerrarCarrito);

    // Click fuera del modal de login → cerrarlo
    document.querySelector('#modal-login')?.addEventListener('click', (e) => {
        if (e.target === document.querySelector('#modal-login')) cerrarModalLogin();
    });

    // Botón X del modal de login
    document.querySelector('#btn-cerrar-modal-login')?.addEventListener('click', cerrarModalLogin);

    // Submit del formulario de login del modal
    document.querySelector('#form-login-modal')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        await procesarLoginModal();
    });

    // Tecla Escape cierra modales
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cerrarCarrito();
            cerrarModalLogin();
        }
    });

    // ── Checkout: "Proceder al Pedido" ────────────────────────────────
    document.querySelector('#btn-checkout')?.addEventListener('click', async () => {
        const items = obtenerItems();
        if (items.length === 0) {
            mostrarToast('El carrito está vacío.', 'warning');
            return;
        }

        const btn = document.querySelector('#btn-checkout');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-bottom-color:transparent;border-radius:50%;animation:spin 1s linear infinite;vertical-align:middle;margin-right:6px;"></span> Procesando...';
        btn.disabled = true;

        try {
            const { crearPedido } = await import('./api.js');
            const res = await crearPedido({ items });

            if (res.resultado === 200) {
                mostrarToast('✅ ' + (res.mensaje || 'Pedido registrado exitosamente'), 'success', 5000);
                vaciarCarrito();
                cerrarCarrito();
            } else {
                mostrarToast(res.mensaje || 'Error al procesar el pedido.', 'error', 5000);
            }
        } catch (err) {
            console.error('Checkout error:', err);
            mostrarToast('Error de conexión con el servidor.', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
};

// ─── Flujo principal: añadir producto ─────────────────────────────────
const manejarAgregarProducto = async (producto, btnOrigen) => {
    // Feedback visual inmediato
    const textoOriginal = btnOrigen.innerHTML;
    btnOrigen.innerHTML = '<span class="spinner"></span> Verificando...';
    btnOrigen.disabled = true;

    try {
        const { autenticado } = await verificarSesion();

        if (autenticado) {
            // ✅ Con sesión: añadir directamente
            agregarAlCarrito(producto);
            mostrarToast(`✅ "${producto.nombre}" añadido al carrito`, 'success');
            animarBotonCarrito();
        } else {
            // 🔒 Sin sesión: guardar producto y mostrar modal de login
            productosPendientes = producto;
            abrirModalLogin();
        }
    } catch {
        mostrarToast('❌ Error de conexión. Intente de nuevo.', 'error');
    } finally {
        btnOrigen.innerHTML = textoOriginal;
        btnOrigen.disabled = false;
    }
};

// ─── Gestión del estado del carrito ───────────────────────────────────
const agregarAlCarrito = (producto) => {
    const existente = estadoCarrito.items.find(i => i.id === producto.id);

    if (existente) {
        existente.cantidad++;
    } else {
        estadoCarrito.items = [...estadoCarrito.items, { ...producto }];
    }

    renderizarCarrito();
    actualizarContadorCarrito();
};

const eliminarDelCarrito = (idProducto) => {
    estadoCarrito.items = estadoCarrito.items.filter(i => i.id !== idProducto);
    renderizarCarrito();
    actualizarContadorCarrito();
};

const cambiarCantidad = (idProducto, delta) => {
    const item = estadoCarrito.items.find(i => i.id === idProducto);
    if (!item) return;

    item.cantidad += delta;
    if (item.cantidad <= 0) {
        eliminarDelCarrito(idProducto);
        return;
    }

    renderizarCarrito();
    actualizarContadorCarrito();
};

export const obtenerItems = () => estadoCarrito.items.map(i => ({
    id_producto:    i.id,
    cantidad:       i.cantidad,
    precio_unitario: i.precio,
}));

export const vaciarCarrito = () => {
    estadoCarrito.items = [];
    renderizarCarrito();
    actualizarContadorCarrito();
};

const calcularTotal = () =>
    estadoCarrito.items.reduce((acc, i) => acc + i.precio * i.cantidad, 0);

// ─── Render del carrito ────────────────────────────────────────────────
const renderizarCarrito = () => {
    const cuerpo  = document.querySelector('#carrito-body');
    const total   = document.querySelector('#carrito-total');
    const checkout = document.querySelector('#btn-checkout');

    if (!cuerpo) return;

    if (estadoCarrito.items.length === 0) {
        cuerpo.innerHTML = `
          <div style="text-align:center; padding:3rem 1rem; color:var(--color-text-muted);">
            <div style="font-size:3rem; margin-bottom:1rem;">🛒</div>
            <p style="font-family:var(--font-admin); font-size:0.9rem;">Tu carrito está vacío</p>
            <p style="font-size:0.75rem; margin-top:0.5rem;">Explora nuestro catálogo y añade tus productos favoritos.</p>
          </div>`;
        if (checkout) checkout.disabled = true;
        if (total) total.textContent = 'Bs. 0.00';
        return;
    }

    // Generar HTML de los items usando template literals
    cuerpo.innerHTML = estadoCarrito.items.map(item => `
      <article class="cart-item" data-id="${item.id}">
        <img class="cart-item__img"
             src="${item.imagen || window.BASE_URL + 'assets/img/placeholder.png'}"
             alt="${item.nombre} — El Trigal Dorado"
             loading="lazy"
             onerror="this.onerror=null; this.src='${window.BASE_URL}assets/img/placeholder.png';">
        <div>
          <p class="cart-item__name">${item.nombre}</p>
          <p class="cart-item__price">Bs. ${(item.precio * item.cantidad).toFixed(2)}</p>
          <div class="cart-item__qty">
            <button data-cambiar="${item.id}" data-delta="-1" aria-label="Reducir cantidad">−</button>
            <span>${item.cantidad}</span>
            <button data-cambiar="${item.id}" data-delta="1"  aria-label="Aumentar cantidad">+</button>
          </div>
        </div>
        <button class="btn-action btn-action--delete"
                data-eliminar="${item.id}"
                aria-label="Eliminar del carrito">✕</button>
      </article>`
    ).join('');

    // Eventos de los controles de cantidad
    cuerpo.querySelectorAll('[data-cambiar]').forEach(btn => {
        btn.addEventListener('click', () => {
            cambiarCantidad(btn.dataset.cambiar, parseInt(btn.dataset.delta));
        });
    });

    cuerpo.querySelectorAll('[data-eliminar]').forEach(btn => {
        btn.addEventListener('click', () => eliminarDelCarrito(btn.dataset.eliminar));
    });

    const montoTotal = calcularTotal();
    if (total)   total.textContent = `Bs. ${montoTotal.toFixed(2)}`;
    if (checkout) checkout.disabled = montoTotal <= 0;
};

const actualizarContadorCarrito = () => {
    const contador = document.querySelector('#cart-count');
    if (!contador) return;

    const total = estadoCarrito.items.reduce((acc, i) => acc + i.cantidad, 0);
    contador.textContent = total;
    contador.style.display = total > 0 ? 'flex' : 'none';
};

// ─── Toggle del Drawer del carrito ────────────────────────────────────
const toggleCarrito = () => {
    estadoCarrito.visible = !estadoCarrito.visible;
    document.querySelector('#carrito-drawer')?.classList.toggle('open', estadoCarrito.visible);
    document.querySelector('#cart-overlay')?.classList.toggle('open', estadoCarrito.visible);
};

const cerrarCarrito = () => {
    estadoCarrito.visible = false;
    document.querySelector('#carrito-drawer')?.classList.remove('open');
    document.querySelector('#cart-overlay')?.classList.remove('open');
};

// ─── Modal de Login (Interceptor) ─────────────────────────────────────
const abrirModalLogin = () => {
    const modal = document.querySelector('#modal-login');
    if (!modal) return;
    modal.classList.add('open');
    setTimeout(() => modal.querySelector('input')?.focus(), 300);
};

const cerrarModalLogin = () => {
    document.querySelector('#modal-login')?.classList.remove('open');
    productosPendientes = null;
};

const procesarLoginModal = async () => {
    const form     = document.querySelector('#form-login-modal');
    const username = form.querySelector('[name="username"]')?.value.trim();
    const password = form.querySelector('[name="password"]')?.value;
    const errorDiv = document.querySelector('#modal-login-error');
    const btnSubmit = form.querySelector('[type="submit"]');

    if (!username || !password) {
        mostrarErrorModal('Por favor, completa todos los campos.');
        return;
    }

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner"></span> Verificando...';

    try {
        const { apiPost } = await import('./api.js');
        const resultado = await apiPost(`${window.BASE_URL}?page=Login`, {
            peticion:  'login',
            username,
            password,
        });

        if (resultado.resultado === 200) {
            // Login exitoso
            cerrarModalLogin();
            mostrarToast('✅ Sesión iniciada correctamente.', 'success');

            // Añadir el producto que estaba pendiente
            if (productosPendientes) {
                agregarAlCarrito(productosPendientes);
                mostrarToast(`✅ "${productosPendientes.nombre}" añadido al carrito`, 'success');
                animarBotonCarrito();
                productosPendientes = null;
            }

            // Actualizar el estado de autenticación en la UI
            document.querySelectorAll('[data-auth-hide]').forEach(el => el.remove());
            document.querySelectorAll('[data-auth-show]').forEach(el => el.style.display = 'flex');
        } else {
            mostrarErrorModal(resultado.mensaje ?? 'Credenciales incorrectas.');
        }
    } catch {
        mostrarErrorModal('Error de conexión. Intente de nuevo.');
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = 'Iniciar Sesión';
    }
};

const mostrarErrorModal = (mensaje) => {
    const div = document.querySelector('#modal-login-error');
    if (!div) return;
    div.textContent = mensaje;
    div.classList.add('visible');
    setTimeout(() => div.classList.remove('visible'), 5000);
};

// ─── Animación del botón del carrito ──────────────────────────────────
const animarBotonCarrito = () => {
    const btn = document.querySelector('#btn-abrir-carrito');
    if (!btn) return;
    btn.classList.add('bump');
    setTimeout(() => btn.classList.remove('bump'), 600);
};

// ─── Sistema de Toasts ────────────────────────────────────────────────
export const mostrarToast = (mensaje, tipo = 'info', duracion = 3500) => {
    let contenedor = document.querySelector('.toast-container');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.className = 'toast-container';
        document.body.appendChild(contenedor);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast--${tipo}`;
    toast.textContent = mensaje;
    contenedor.appendChild(toast);

    // Forzar reflow para que la transición funcione
    toast.getBoundingClientRect();
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, duracion);
};
