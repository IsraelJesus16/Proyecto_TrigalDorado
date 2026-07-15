/**
 * pedidos.js — Módulo para gestión de pedidos en el Panel Administrativo
 * El Trigal Dorado TPS · Vanilla JS ES6+ Modules
 */

import { apiPost } from './api.js';

// SweetAlert2 Toast configuration
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
});

function mostrarToast(mensaje, icon = 'info') {
    Toast.fire({ icon, title: mensaje });
}

document.addEventListener('DOMContentLoaded', () => {

    const modalDetalle = document.getElementById('modal-detalle-pedido');
    const bodyDetalle = document.getElementById('modal-detalle-body');
    const footerDetalle = document.getElementById('modal-detalle-footer');

    // ── Filtros y DataTable ───────────────────────────────────────────
    let tablaPedidos;
    if (window.$ && $.fn.DataTable) {
        tablaPedidos = $('#tabla-pedidos').DataTable({
            ajax: {
                url: `${window.BASE_URL}?page=Pedido&type=ajax`,
                type: 'POST',
                data: { peticion: 'listar' },
                dataSrc: function (json) {
                    if (json.resultado === 200 && json.data) {
                        return json.data;
                    }
                    return [];
                }
            },
            columns: [
                { data: 'numero_pedido', render: data => `<strong>${data}</strong>` },
                { 
                    data: null, 
                    render: function (data) {
                        return `<div>${data.nombre} ${data.apellido}</div>
                                <div style="font-size:0.7rem; color:var(--color-text-muted);">${data.cedula_cliente}</div>`;
                    } 
                },
                { 
                    data: 'fecha_pedido', 
                    render: function(data) {
                        const d = new Date(data);
                        return isNaN(d) ? data : d.toLocaleDateString('es-VE') + ' ' + d.toLocaleTimeString('es-VE', {hour: '2-digit', minute:'2-digit'});
                    } 
                },
                { 
                    data: 'condicion_pago',
                    render: function(data) {
                        if (data === 'CREDITO') return `<span class="badge" style="background:rgba(155,89,182,0.1); color:#9b59b6;">CRÉDITO</span>`;
                        return `<span class="badge" style="background:rgba(46,204,113,0.1); color:#27ae60;">CONTADO</span>`;
                    }
                },
                { data: 'total', render: data => `<strong>Bs. ${parseFloat(data).toFixed(2)}</strong>` },
                { 
                    data: 'estado',
                    render: function(data) {
                        return `<span class="badge badge--${data.toLowerCase()}">${data}</span>`;
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: function(data) {
                        let btns = `
                            <button class="btn-action btn-action--view btn-ver-detalle" data-id="${data.id_pedido}" aria-label="Ver detalles" title="Ver Detalles">
                                <box-icon name='show' size='xs' animation='tada-hover' color='var(--color-info)'></box-icon>
                            </button>
                        `;
                        if (data.estado === 'PENDIENTE') {
                            btns += `
                                <button class="btn-action btn-action--success btn-cambiar-estado" data-id="${data.id_pedido}" data-estado="CONFIRMADO" aria-label="Confirmar" title="Confirmar Pedido">
                                    <box-icon name='check-circle' size='xs' animation='tada-hover' color='#27ae60'></box-icon>
                                </button>
                                <button class="btn-action btn-action--delete btn-cancelar" data-id="${data.id_pedido}" aria-label="Cancelar" title="Cancelar Pedido">
                                    <box-icon name='x-circle' size='xs' animation='tada-hover' color='var(--color-danger)'></box-icon>
                                </button>
                            `;
                        }
                        return btns;
                    }
                }
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
            order: [[0, 'desc']],
            pageLength: 10,
            dom: '<"top"l>rt<"bottom"ip><"clear">'
        });
        
        $('#buscar-pedido').on('keyup', function() {
            tablaPedidos.search(this.value).draw();
        });
        $('#filtro-estado').on('change', function() {
            if(this.value === "") {
                tablaPedidos.column(5).search("").draw();
            } else {
                tablaPedidos.column(5).search(`^${this.value}$`, true, false).draw();
            }
        });
    }

    // ── Cerrar Modales ────────────────────────────────────────────────
    document.querySelectorAll('[data-accion="cerrar-modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.admin-modal-overlay').classList.remove('open');
        });
    });

    // ── Ver Detalle (AJAX) y Delegación ───────────────────────────────
    document.addEventListener('click', async (e) => {
        const btnVer = e.target.closest('.btn-ver-detalle');
        if (btnVer) {
            const idPedido = btnVer.dataset.id;
            
            // UI de carga
            document.getElementById('det-numero').textContent = '...';
            bodyDetalle.innerHTML = `
                <div class="skeleton skeleton--row"></div>
                <div class="skeleton skeleton--row"></div>
                <div class="skeleton skeleton--row"></div>
            `;
            modalDetalle.classList.add('open');

            try {
                const res = await apiPost(`${window.BASE_URL}?page=Pedido&type=ajax`, {
                    peticion: 'buscar',
                    id_pedido: idPedido
                });

                if (res.resultado === 200 && res.datos) {
                    const ped = res.datos;
                    const items = ped.detalles || [];

                    document.getElementById('det-numero').textContent = `#${ped.numero_pedido}`;
                    
                    let html = `
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem; background:var(--color-bg); padding:1rem; border-radius:8px;">
                            <div>
                                <p style="font-size:0.75rem; color:var(--color-text-muted); text-transform:uppercase;">Cliente</p>
                                <p style="font-weight:600;">${ped.nombre} ${ped.apellido}</p>
                                <p style="font-size:0.85rem;">CI: ${ped.cedula_cliente}</p>
                            </div>
                            <div>
                                <p style="font-size:0.75rem; color:var(--color-text-muted); text-transform:uppercase;">Condiciones</p>
                                <p style="font-weight:600;">Pago: ${ped.condicion_pago} / ${ped.metodo_pago}</p>
                                <p style="font-size:0.85rem;">Estado: <span class="badge badge--${ped.estado.toLowerCase()}">${ped.estado}</span></p>
                            </div>
                        </div>
                        
                        <h4 style="margin-bottom:0.5rem; font-size:0.9rem; color:var(--color-secondary);">Productos Solicitados</h4>
                        <table class="data-table" style="margin-bottom:1.5rem;">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cant.</th>
                                    <th>Precio Unit.</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    let subtotal = 0;
                    items.forEach(item => {
                        const totalItem = item.cantidad * item.precio_unitario;
                        subtotal += totalItem;
                        html += `
                            <tr>
                                <td>${item.nombre_producto}</td>
                                <td>${item.cantidad}</td>
                                <td>Bs. ${parseFloat(item.precio_unitario).toFixed(2)}</td>
                                <td><strong>Bs. ${totalItem.toFixed(2)}</strong></td>
                            </tr>
                        `;
                    });

                    const desc = parseFloat(ped.descuento || 0);
                    const total = subtotal - desc;

                    html += `
                            </tbody>
                        </table>
                        <div style="text-align:right; font-size:1.1rem;">
                            <p>Subtotal: Bs. ${subtotal.toFixed(2)}</p>
                            ${desc > 0 ? `<p style="color:var(--color-danger);">Descuento: -Bs. ${desc.toFixed(2)}</p>` : ''}
                            <p style="font-weight:bold; font-size:1.3rem; margin-top:0.5rem;">Total: Bs. ${total.toFixed(2)}</p>
                        </div>
                    `;

                    if (ped.observacion) {
                        html += `
                            <div style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--color-border);">
                                <p style="font-size:0.75rem; font-weight:600;">Observación:</p>
                                <p style="font-size:0.85rem; font-style:italic;">"${ped.observacion}"</p>
                            </div>
                        `;
                    }

                    bodyDetalle.innerHTML = html;

                    // Reconstruir footer de acciones
                    let accionesHTML = `<button class="btn--admin-secondary" data-accion="cerrar-modal">Cerrar</button>`;
                    
                    if (ped.estado === 'PENDIENTE') {
                        accionesHTML += `<button class="btn--admin-primary btn-accion-estado" data-id="${ped.id_pedido}" data-estado="CONFIRMADO">Aprobar Pedido</button>`;
                        accionesHTML += `<button class="btn--admin-danger btn-accion-cancelar" data-id="${ped.id_pedido}">Cancelar</button>`;
                    } else if (ped.estado === 'CONFIRMADO') {
                        accionesHTML += `<button class="btn--admin-primary btn-accion-estado" data-id="${ped.id_pedido}" data-estado="PROCESANDO">Enviar a Producción</button>`;
                    } else if (ped.estado === 'PROCESANDO') {
                        accionesHTML += `<button class="btn--admin-primary btn-accion-estado" data-id="${ped.id_pedido}" data-estado="DESPACHADO">Marcar como Despachado</button>`;
                    } else if (ped.estado === 'DESPACHADO') {
                        accionesHTML += `<button class="btn--admin-success btn-accion-estado" data-id="${ped.id_pedido}" data-estado="ENTREGADO" style="background:#27ae60; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:bold;">Confirmar Entrega</button>`;
                    }

                    footerDetalle.innerHTML = accionesHTML;

                    // Re-bind eventos del modal footer
                    footerDetalle.querySelector('[data-accion="cerrar-modal"]')?.addEventListener('click', () => {
                        modalDetalle.classList.remove('open');
                    });
                    
                    footerDetalle.querySelectorAll('.btn-accion-estado').forEach(b => {
                        b.addEventListener('click', () => cambiarEstadoPedido(b.dataset.id, b.dataset.estado));
                    });
                    
                    footerDetalle.querySelector('.btn-accion-cancelar')?.addEventListener('click', (e) => {
                        cancelarPedido(e.target.dataset.id);
                    });

                } else {
                    bodyDetalle.innerHTML = `<p class="alert-error">Error: ${res.mensaje}</p>`;
                }
            } catch (e) {
                bodyDetalle.innerHTML = `<p class="alert-error">Error de conexión con el servidor.</p>`;
            }
        }

        const btnEstado = e.target.closest('.btn-cambiar-estado');
        if (btnEstado) {
            cambiarEstadoPedido(btnEstado.dataset.id, btnEstado.dataset.estado);
        }

        const btnCancelar = e.target.closest('.btn-cancelar');
        if (btnCancelar) {
            cancelarPedido(btnCancelar.dataset.id);
        }
    });

    // ── Funciones de Acción ──────────────────────────────────────────
    const cambiarEstadoPedido = async (idPedido, nuevoEstado) => {
        const confirmacion = await Swal.fire({
            title: `¿Pasar a ${nuevoEstado}?`,
            text: `El pedido cambiará de estado y se avanzará en su ciclo.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'var(--color-primary)',
            cancelButtonColor: 'var(--color-secondary)',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const res = await apiPost(`${window.BASE_URL}?page=Pedido&type=ajax`, {
                peticion: 'cambiar_estado',
                id_pedido: idPedido,
                estado: nuevoEstado
            });

            if (res.resultado === 200) {
                mostrarToast(`Pedido actualizado a ${nuevoEstado}`, 'success');
                if (tablaPedidos) tablaPedidos.ajax.reload(null, false);
                modalDetalle.classList.remove('open');
            } else {
                mostrarToast(res.mensaje || 'Error al cambiar estado.', 'error');
            }
        } catch (e) {
            mostrarToast(`Error de conexión: ${e.message}`, 'error');
        }
    };

    const cancelarPedido = async (idPedido) => {
        const confirmacion = await Swal.fire({
            title: '¿Cancelar Pedido?',
            text: '¿Estás SEGURO de cancelar este pedido? Esta acción liberará el inventario reservado y NO se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--color-danger)',
            cancelButtonColor: 'var(--color-secondary)',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, mantener'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const res = await apiPost(`${window.BASE_URL}?page=Pedido&type=ajax`, {
                peticion: 'cancelar',
                id_pedido: idPedido
            });

            if (res.resultado === 200) {
                mostrarToast(`Pedido cancelado y stock liberado correctamente.`, 'success');
                if (tablaPedidos) tablaPedidos.ajax.reload(null, false);
                modalDetalle.classList.remove('open');
            } else {
                mostrarToast(res.mensaje || 'Error al cancelar pedido.', 'error');
            }
        } catch (e) {
            mostrarToast(`Error de conexión: ${e.message}`, 'error');
        }
    };

    // ════════════════════════════════════════════════════════════════════
    // PUNTO DE VENTA (POS) - CREACIÓN MANUAL DE PEDIDOS
    // ════════════════════════════════════════════════════════════════════

    const modalPos = document.getElementById('modal-nuevo-pedido');
    const btnNuevoPedido = document.getElementById('btn-nuevo-pedido');
    const selectPosCedula = document.getElementById('pos-cedula');
    const btnNuevoClientePos = document.getElementById('btn-nuevo-cliente-pos');
    const panelClienteInfo = document.getElementById('pos-cliente-info');
    // Selectores Productos y Carrito
    const selectProducto = document.getElementById('pos-producto');
    const inputCantidad = document.getElementById('pos-cantidad');
    const btnAgregarProducto = document.getElementById('btn-agregar-producto');
    const tbodyCart = document.getElementById('pos-cart-tbody');
    const posSubtotal = document.getElementById('pos-subtotal');
    const posDescuento = document.getElementById('pos-descuento');
    const posTotal = document.getElementById('pos-total');
    const btnProcesarPedido = document.getElementById('btn-procesar-pedido');
    const selectCondicion = document.getElementById('pos-condicion');
    const selectMetodo = document.getElementById('pos-metodo');
    const inputObservacion = document.getElementById('pos-observacion');

    let posCart = [];
    let clienteActual = null;
    let productosDisponibles = [];

    // ── Abrir Modal ──────────────────────────────────────────────────
    btnNuevoPedido?.addEventListener('click', async () => {
        // Limpiar estado
        posCart = [];
        clienteActual = null;
        if($.fn.select2) {
            $(selectPosCedula).val(null).trigger('change');
        }
        panelClienteInfo.style.display = 'none';
        selectProducto.innerHTML = '<option value="">Cargando productos...</option>';
        modalPos.classList.add('open');

        // Inicializar Select2 en el modal
        if($.fn.select2) {
            $(selectPosCedula).select2({
                placeholder: "Seleccione o busque un cliente...",
                allowClear: true,
                dropdownParent: $('#modal-nuevo-pedido')
            });
        }

        try {
            const formData = new FormData();
            formData.append('peticion', 'consultar');
            formData.append('solo_activos', '1');
            
            const res = await fetch(`${window.BASE_URL}?page=Producto&type=ajax&action=consultar`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.estado === 1 && data.response && data.response.datos) {
                productosDisponibles = data.response.datos;
                let options = '<option value="">Seleccione un producto...</option>';
                productosDisponibles.forEach(p => {
                    options += `<option value="${p.id_producto}" data-precio="${p.precio_venta}">
                        ${p.nombre} (Bs. ${parseFloat(p.precio_venta).toFixed(2)})
                    </option>`;
                });
                selectProducto.innerHTML = options;
            } else {
                selectProducto.innerHTML = '<option value="">No hay productos</option>';
            }

            // Cargar clientes para Select2
            const reqCli = await fetch(`${window.BASE_URL}?page=Cliente&type=ajax&action=consultar`);
            const resCli = await reqCli.json();
            
            if (resCli.estado === 1 && resCli.response && resCli.response.datos) {
                let cliOptions = '<option value="">Seleccione o busque un cliente...</option>';
                resCli.response.datos.forEach(c => {
                    cliOptions += `<option value="${c.cedula}" data-nombre="${c.nombre} ${c.apellido}" data-condicion="${c.condicion_pago}" data-tipo="${c.tipo_cliente}">
                        ${c.cedula} - ${c.nombre} ${c.apellido}
                    </option>`;
                });
                $(selectPosCedula).html(cliOptions);
            } else {
                $(selectPosCedula).html('<option value="">No hay clientes</option>');
            }

        } catch (e) {
            selectProducto.innerHTML = '<option value="">Error al cargar catálogo</option>';
            $(selectPosCedula).html('<option value="">Error al cargar clientes</option>');
        }
    });

    // ── Buscar o Crear Cliente ───────────────────────────────────────
    $(selectPosCedula).on('select2:select', function (e) {
        const option = e.params.data.element;
        if(!option || !option.value) return;

        clienteActual = option.value;
        const nombre = option.getAttribute('data-nombre');
        const condicion = option.getAttribute('data-condicion');
        const tipo = option.getAttribute('data-tipo');

        panelClienteInfo.style.display = 'block';
        panelClienteInfo.innerHTML = `
            <div style="display:flex; align-items:center; gap:10px; color:var(--color-success); margin-bottom:5px;">
                <box-icon name='check-circle' color='green'></box-icon>
                <strong>Cliente Seleccionado</strong>
            </div>
            <p style="margin:0;"><strong>Nombre:</strong> ${nombre}</p>
            <p style="margin:0; font-size:0.85rem; color:var(--color-text-muted);"><strong>Condición:</strong> ${condicion} | <strong>Tipo:</strong> ${tipo}</p>
        `;
        checkPosState();
    });

    $(selectPosCedula).on('select2:unselect', function (e) {
        clienteActual = null;
        panelClienteInfo.style.display = 'none';
        checkPosState();
    });

    btnNuevoClientePos?.addEventListener('click', (e) => {
        e.preventDefault();
        clienteActual = null;
        $(selectPosCedula).val(null).trigger('change');
        checkPosState();
        
        panelClienteInfo.style.display = 'block';
        panelClienteInfo.innerHTML = `
            <div style="display:flex; align-items:center; gap:10px; color:var(--color-primary); margin-bottom:10px;">
                <box-icon name='user-plus' color='var(--color-primary)'></box-icon>
                <strong>Registrar Cliente Rápido</strong>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <div style="display:flex; gap:5px; grid-column: span 2;">
                    <select id="pos-nuevo-doc" class="form-select" style="width:70px; padding:0.4rem;">
                        <option value="V">V</option>
                        <option value="E">E</option>
                        <option value="J">J</option>
                        <option value="G">G</option>
                    </select>
                    <input type="text" id="pos-nuevo-cedula" class="form-control" placeholder="12345678" style="flex:1; padding:0.4rem;" maxlength="9">
                </div>
                <input type="text" id="pos-nuevo-nombre" class="form-control" placeholder="Nombre" style="padding:0.4rem;">
                <input type="text" id="pos-nuevo-apellido" class="form-control" placeholder="Apellido" style="padding:0.4rem;">
                <input type="text" id="pos-nuevo-telefono" class="form-control" placeholder="0412-1234567" style="grid-column: span 2; padding:0.4rem;" maxlength="12">
                <button class="btn--admin-primary" id="btn-registrar-cliente-rapido" style="grid-column: span 2; padding:8px; font-size:0.85rem;">Registrar y Seleccionar</button>
            </div>
        `;

        // Mascaras y formato
        const iCed = document.getElementById('pos-nuevo-cedula');
        const iNom = document.getElementById('pos-nuevo-nombre');
        const iApe = document.getElementById('pos-nuevo-apellido');
        const iTel = document.getElementById('pos-nuevo-telefono');
        const sDoc = document.getElementById('pos-nuevo-doc');

        iCed.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
        const capitalize = (e) => {
            e.target.value = e.target.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '')
                                           .replace(/\b\w/g, l => l.toUpperCase());
        };
        iNom.addEventListener('input', capitalize);
        iApe.addEventListener('input', capitalize);
        iTel.addEventListener('input', (e) => {
            let val = e.target.value.replace(/[^0-9]/g, '');
            if (val.length > 4) val = val.slice(0,4) + '-' + val.slice(4);
            e.target.value = val;
        });

        document.getElementById('btn-registrar-cliente-rapido').addEventListener('click', async (ev) => {
            ev.preventDefault();
            const nCedVal = iCed.value.trim();
            const nCed = `${sDoc.value}-${nCedVal}`;
            const nNom = iNom.value.trim();
            const nApe = iApe.value.trim();
            const nTel = iTel.value.trim();

            if (!nCedVal || !nNom || !nApe) return mostrarToast('Cédula, Nombre y Apellido requeridos', 'warning');
            if (nTel && nTel.length < 12) return mostrarToast('Teléfono incompleto', 'warning');

            const fdc = new FormData();
            fdc.append('cedula', nCed);
            fdc.append('nombre', nNom);
            fdc.append('apellido', nApe);
            fdc.append('telefono', nTel);
            fdc.append('tipo_cliente', (sDoc.value === 'J' || sDoc.value === 'G') ? 'JURIDICO' : 'NATURAL');
            fdc.append('condicion_pago', 'CONTADO');

            try {
                const rC = await fetch(`${window.BASE_URL}?page=Cliente&action=guardar`, { method: 'POST', body: fdc });
                const dC = await rC.json();

                if (dC.response && dC.response.resultado === 200) {
                    mostrarToast('Cliente registrado', 'success');
                    // Add new option to Select2 and select it
                    const newOption = new Option(`${nCed} - ${nNom} ${nApe}`, nCed, true, true);
                    newOption.setAttribute('data-nombre', `${nNom} ${nApe}`);
                    newOption.setAttribute('data-condicion', 'CONTADO');
                    newOption.setAttribute('data-tipo', fdc.get('tipo_cliente'));
                    $(selectPosCedula).append(newOption).trigger('change');
                    $(selectPosCedula).trigger({
                        type: 'select2:select',
                        params: { data: { element: newOption, value: nCed } }
                    });
                } else {
                    mostrarToast(dC.response?.mensaje || 'Error registrando cliente', 'error');
                }
            } catch(error) {
                mostrarToast('Error de conexión', 'error');
            }
        });
    });

    // ── Carrito ──────────────────────────────────────────────────────
    btnAgregarProducto?.addEventListener('click', () => {
        const idProd = selectProducto.value;
        const cant = parseInt(inputCantidad.value);

        if (!idProd) return mostrarToast('Seleccione un producto', 'warning');
        if (cant < 1) return mostrarToast('Cantidad inválida', 'warning');

        const prodSelect = productosDisponibles.find(p => p.id_producto === idProd);
        if (!prodSelect) return;

        // Verificar si ya existe en el carrito
        const existe = posCart.find(i => i.id_producto === idProd);
        if (existe) {
            existe.cantidad += cant;
        } else {
            posCart.push({
                id_producto: prodSelect.id_producto,
                nombre: prodSelect.nombre,
                precio_unitario: parseFloat(prodSelect.precio_venta),
                cantidad: cant
            });
        }

        inputCantidad.value = '1';
        selectProducto.value = '';
        renderCart();
    });

    const renderCart = () => {
        if (posCart.length === 0) {
            tbodyCart.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:2rem; color:var(--color-text-muted);">El carrito está vacío.</td></tr>`;
            posSubtotal.textContent = 'Bs. 0.00';
            posTotal.textContent = 'Bs. 0.00';
            checkPosState();
            return;
        }

        let html = '';
        let subtotal = 0;

        posCart.forEach((item, index) => {
            const stItem = item.cantidad * item.precio_unitario;
            subtotal += stItem;
            html += `
                <tr>
                    <td style="font-size:0.85rem; font-weight:500;">${item.nombre}</td>
                    <td>
                        <input type="number" class="form-control form-control--sm" value="${item.cantidad}" min="1" 
                               style="width:50px; padding:2px;" data-index="${index}" onchange="window.updatePosQty(${index}, this.value)">
                    </td>
                    <td style="font-size:0.85rem;">Bs. ${item.precio_unitario.toFixed(2)}</td>
                    <td style="font-weight:bold; font-size:0.85rem;">Bs. ${stItem.toFixed(2)}</td>
                    <td>
                        <button class="btn-action btn-action--delete" onclick="window.removePosItem(${index})">
                            <box-icon name='trash' size='xs' color='var(--color-danger)'></box-icon>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbodyCart.innerHTML = html;
        posSubtotal.textContent = `Bs. ${subtotal.toFixed(2)}`;
        calcularTotal(subtotal);
        checkPosState();
    };

    window.updatePosQty = (index, val) => {
        const n = parseInt(val);
        if (n > 0) {
            posCart[index].cantidad = n;
            renderCart();
        }
    };

    window.removePosItem = (index) => {
        posCart.splice(index, 1);
        renderCart();
    };

    posDescuento?.addEventListener('input', () => {
        let subtotal = 0;
        posCart.forEach(i => subtotal += (i.cantidad * i.precio_unitario));
        calcularTotal(subtotal);
    });

    const calcularTotal = (subtotal) => {
        const desc = parseFloat(posDescuento.value) || 0;
        const total = Math.max(0, subtotal - desc);
        posTotal.textContent = `Bs. ${total.toFixed(2)}`;
    };

    const checkPosState = () => {
        if (clienteActual && posCart.length > 0) {
            btnProcesarPedido.style.opacity = '1';
            btnProcesarPedido.style.pointerEvents = 'auto';
        } else {
            btnProcesarPedido.style.opacity = '0.5';
            btnProcesarPedido.style.pointerEvents = 'none';
        }
    };

    // ── Enviar Pedido ────────────────────────────────────────────────
    btnProcesarPedido?.addEventListener('click', async () => {
        if (!clienteActual || posCart.length === 0) return;

        btnProcesarPedido.innerHTML = '<box-icon name="loader-alt" animation="spin" color="#fff"></box-icon> Procesando...';
        btnProcesarPedido.style.pointerEvents = 'none';

        try {
            const res = await apiPost(`${window.BASE_URL}?page=Pedido&type=ajax`, {
                peticion: 'crear',
                cedula_cliente: clienteActual,
                condicion_pago: selectCondicion.value,
                metodo_pago: selectMetodo.value,
                descuento: posDescuento.value || 0,
                observacion: inputObservacion.value.trim(),
                items: JSON.stringify(posCart.map(i => ({
                    id_producto: i.id_producto,
                    cantidad: i.cantidad,
                    precio_unitario: i.precio_unitario
                })))
            });

            if (res.resultado === 200) {
                mostrarToast(`Pedido #${res.numero_pedido} registrado con éxito`, 'success');
                if (tablaPedidos) tablaPedidos.ajax.reload(null, false);
                document.querySelector('.admin-modal-overlay.open')?.classList.remove('open');
            } else {
                // Posibles bloqueos por Regla 1 (Morosidad), Regla 2 (Probación) o Regla 3 (Stock)
                Swal.fire({
                    title: res.icon === 'warning' ? 'Aviso del Sistema' : 'Error',
                    text: res.mensaje,
                    icon: res.icon || 'error',
                    confirmButtonColor: 'var(--color-primary)'
                });
                btnProcesarPedido.innerHTML = 'PROCESAR PEDIDO';
                btnProcesarPedido.style.pointerEvents = 'auto';
            }
        } catch (e) {
            mostrarToast(`Error de conexión: ${e.message}`, 'error');
            btnProcesarPedido.innerHTML = 'PROCESAR PEDIDO';
            btnProcesarPedido.style.pointerEvents = 'auto';
        }
    });

});
