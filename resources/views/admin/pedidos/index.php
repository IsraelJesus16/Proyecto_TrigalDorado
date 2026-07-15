<?php require_once BASE_PATH . '/resources/views/layout/head-admin.php'; ?>
<?php require_once BASE_PATH . '/resources/views/layout/sidebar-admin.php'; ?>

    <div class="page-content">
        <header class="page-header">
            <div>
                <h1 class="page-header__title">Gestión de Pedidos</h1>
                <p class="page-header__subtitle">Control de órdenes y ciclo de vida de las ventas (Reglas de Negocio 1, 2 y 3).</p>
            </div>
            <div>
                <button class="btn--admin-primary" id="btn-nuevo-pedido" style="display:flex;align-items:center;gap:6px;">
                    <box-icon name='plus-circle' size='sm' color='#fff' animation='tada-hover'></box-icon> Nuevo Pedido
                </button>
            </div>
        </header>

        <section class="table-card">
            <header class="table-card__header">
                <h3 class="table-card__title">Historial de Pedidos</h3>
                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <div style="position:relative;">
                        <box-icon name='search' size='sm' color='var(--color-text-muted)' style="position:absolute; left:10px; top:50%; transform:translateY(-50%);"></box-icon>
                        <input type="text" id="buscar-pedido" class="form-control" placeholder="Buscar pedido..." style="padding-left:36px; max-width:250px; border-radius:20px; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                    </div>
                    <div style="position:relative;">
                        <box-icon name='filter-alt' size='sm' color='var(--color-text-muted)' style="position:absolute; left:10px; top:50%; transform:translateY(-50%); z-index:1; pointer-events:none;"></box-icon>
                        <select id="filtro-estado" class="form-select" style="padding-left:36px; padding-right:36px; max-width:200px; border-radius:20px; appearance:none; box-shadow:0 2px 4px rgba(0,0,0,0.02); cursor:pointer;">
                            <option value="">Todos los estados</option>
                            <option value="PENDIENTE">Pendientes</option>
                            <option value="CONFIRMADO">Confirmados</option>
                            <option value="PROCESANDO">En Producción</option>
                            <option value="DESPACHADO">Despachados</option>
                            <option value="ENTREGADO">Entregados</option>
                            <option value="CANCELADO">Cancelados</option>
                        </select>
                        <box-icon name='chevron-down' size='sm' color='var(--color-text-muted)' style="position:absolute; right:10px; top:50%; transform:translateY(-50%); pointer-events:none;"></box-icon>
                    </div>
                </div>
            </header>
            <div class="table-wrap">
                <table class="data-table" id="tabla-pedidos">
                    <thead>
                        <tr>
                            <th>N° Pedido</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Condición</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-pedidos-body">
                    </tbody>
                </table>
            </div>
        </section>

    </div><!-- /page-content -->
</main><!-- /admin-main -->

<!-- Modal Detalles Pedido -->
<div class="admin-modal-overlay" id="modal-detalle-pedido">
    <div class="admin-modal admin-modal--lg">
        <header class="admin-modal__header">
            <h2 class="admin-modal__title">Detalle de Pedido <span id="det-numero" style="color:var(--color-primary);"></span></h2>
            <button class="admin-modal__close" data-accion="cerrar-modal">✕</button>
        </header>

        <div class="admin-modal__body" id="modal-detalle-body">
            <div class="skeleton skeleton--row"></div>
            <div class="skeleton skeleton--row"></div>
            <div class="skeleton skeleton--row"></div>
        </div>

        <footer class="admin-modal__footer" id="modal-detalle-footer">
            <button class="btn--admin-secondary" data-accion="cerrar-modal">Cerrar</button>
        </footer>
    </div>
</div>

<!-- Modal para Nuevo Pedido (POS Admin) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Homologar Select2 con .form-control de admin.css */
    .select2-container--default .select2-selection--single {
        height: 42px;
        padding: 5px 14px;
        border: 1.5px solid var(--color-border);
        border-radius: var(--radius-sm);
        background-color: var(--color-bg);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
        color: var(--color-text);
        padding-left: 0;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: var(--color-primary);
        background: #fff;
        box-shadow: 0 0 0 3px var(--color-primary-glow);
    }
</style>
<div class="admin-modal-overlay" id="modal-nuevo-pedido">
    <div class="admin-modal" style="max-width: 800px;">
        <header class="admin-modal__header">
            <h3 class="admin-modal__title">Nuevo Pedido</h3>
            <button class="admin-modal__close" data-accion="cerrar-modal">✕</button>
        </header>

        <div class="admin-modal__body admin-form" style="padding: 2rem;">
            
            <!-- 1. Datos del Cliente -->
            <div class="form-section-header" style="margin-top: 0; margin-bottom: 0.5rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem;">
                <h4 style="color: var(--color-primary); margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;"><box-icon name='user-pin' color='inherit' size='sm'></box-icon> Datos del Cliente</h4>
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem; margin-bottom:1.5rem;">
                <label class="form-label">Buscar por Cédula de Identidad o Nombre <span class="required">*</span></label>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <div style="flex:1;">
                        <select id="pos-cedula" class="form-control" style="width:100%;">
                            <option value="">Cargando clientes...</option>
                        </select>
                    </div>
                    <button class="btn--admin-secondary" id="btn-nuevo-cliente-pos" style="height:42px; display:flex; align-items:center; gap:5px; white-space:nowrap;">
                        <box-icon name='user-plus' size='xs' color='var(--color-text)'></box-icon> Nuevo
                    </button>
                </div>
            </div>
            
            <!-- Tarjeta del cliente encontrado o Formulario rápido -->
            <div id="pos-cliente-info" style="display:none; padding:1rem; background:var(--bg-body); border-radius:4px; margin-bottom:1.5rem;">
                <!-- Se llena con JS -->
            </div>

            <!-- 2. Agregar Productos -->
            <div class="form-section-header" style="margin-top: 1rem; margin-bottom: 0.5rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem;">
                <h4 style="color: var(--color-primary); margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;"><box-icon name='package' color='inherit' size='sm'></box-icon> Catálogo de Productos</h4>
            </div>

            <div class="form-group" style="display:flex; gap:0.5rem; margin-bottom:1rem; align-items:flex-end;">
                <div style="flex:2; display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label">Seleccione un producto</label>
                    <select id="pos-producto" class="form-select">
                        <option value="">Cargando productos...</option>
                    </select>
                </div>
                <div style="flex:1; max-width:100px; display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label">Cantidad</label>
                    <input type="number" id="pos-cantidad" class="form-control" value="1" min="1" style="text-align:center;">
                </div>
                <button class="btn--admin-secondary" id="btn-agregar-producto" style="height:42px;">Agregar</button>
            </div>
            
            <div class="table-wrap" style="max-height: 250px; overflow-y:auto; border:1px solid var(--color-border); border-radius:4px; margin-bottom:1.5rem;">
                        <table class="data-table" style="margin:0;">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cant.</th>
                                    <th>P. Unit</th>
                                    <th>Subtotal</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="pos-cart-tbody">
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:2rem; color:var(--color-text-muted);">
                                        El carrito está vacío.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
            </div>

            <!-- 3. Resumen Financiero -->
            <div class="form-section-header" style="margin-top: 1rem; margin-bottom: 0.5rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem;">
                <h4 style="color: var(--color-primary); margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;"><box-icon name='receipt' color='inherit' size='sm'></box-icon> Resumen y Pago</h4>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label">Condición de Pago <span class="required">*</span></label>
                    <select id="pos-condicion" class="form-select" style="padding: 0.6rem 1rem;">
                        <option value="CONTADO">Contado</option>
                        <option value="CREDITO">Crédito</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label">Método de Pago <span class="required">*</span></label>
                    <select id="pos-metodo" class="form-select" style="padding: 0.6rem 1rem;">
                        <option value="EFECTIVO">Efectivo</option>
                        <option value="TRANSFERENCIA">Transferencia</option>
                        <option value="PAGO_MOVIL">Pago Móvil</option>
                        <option value="DIVISAS">Divisas</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem; margin-bottom:1.5rem;">
                <label class="form-label">Observación / Notas</label>
                <textarea id="pos-observacion" class="form-control" rows="1" placeholder="Dirección de entrega, indicaciones, etc..."></textarea>
            </div>

            <div style="background:var(--bg-body); padding:1.5rem; border-radius:4px; border:1px solid var(--color-border);">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:1rem; color:var(--color-text-muted);">
                    <span>Subtotal:</span>
                    <strong id="pos-subtotal" style="color:var(--color-text);">Bs. 0.00</strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:15px; font-size:1rem; align-items:center; color:var(--color-text-muted);">
                    <span>Descuento (Bs.):</span>
                    <input type="number" id="pos-descuento" class="form-control" value="0" min="0" step="0.1" style="max-width:150px; text-align:right;">
                </div>
                <div style="display:flex; justify-content:space-between; padding-top:15px; border-top:1px solid var(--color-border); font-size:1.3rem; align-items:center;">
                    <strong>Total a Pagar:</strong>
                    <strong id="pos-total" style="color:var(--color-primary-dark); font-size:1.5rem;">Bs. 0.00</strong>
                </div>
            </div>

        </div>

        <footer class="admin-modal__footer">
            <button class="btn--admin-secondary" data-accion="cerrar-modal">Cancelar</button>
            <button class="btn--admin-primary" id="btn-procesar-pedido" style="opacity:0.5; pointer-events:none;">Guardar Pedido</button>
        </footer>
    </div>
</div>

<!-- Ocultar loader al cargar -->
<script>
    window.addEventListener('load', () => {
        const loader = document.getElementById('admin-loader');
        if (loader) loader.style.display = 'none';
    });
</script>

<!-- Scripts -->
<script src="<?php echo BASE_URL; ?>assets/js/vendor/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/vendor/sweetalert2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="module" src="<?php echo BASE_URL; ?>assets/js/modules/pedidos.js?v=<?php echo time(); ?>"></script>

</body>
</html>
