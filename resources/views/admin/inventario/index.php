<?php require_once BASE_PATH . '/resources/views/layout/head-admin.php'; ?>
<?php require_once BASE_PATH . '/resources/views/layout/sidebar-admin.php'; ?>


    <div class="page-content">
        <header class="page-header">
            <div>
                <h1 class="page-header__title">Inventario y Stock</h1>
                <p class="page-header__subtitle">Control de existencias físicas y ajustes manuales de productos e insumos.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn--admin-secondary" id="btn-ajuste-mp" style="display:flex; align-items:center; gap:6px;">
                    <box-icon name='package' color='inherit' size='sm'></box-icon> Ajuste Insumo
                </button>
                <button class="btn--admin-primary" id="btn-ajuste" style="display:flex; align-items:center; gap:6px;">
                    <box-icon name='slider' color='inherit' size='sm'></box-icon> Ajuste Producto
                </button>
            </div>
        </header>

        <!-- ── Sistema de Tabs ─────────────────────────────────────────── -->
        <div class="inv-tabs">
            <div class="inv-tab-indicator" id="inv-tab-indicator"></div>
            <button class="inv-tab active" data-tab="productos" id="tab-btn-productos">
                <box-icon name='package' size='xs' color='inherit'></box-icon>
                Stock de Productos
            </button>
            <button class="inv-tab" data-tab="materiaprima" id="tab-btn-mp">
                <box-icon name='leaf' size='xs' color='inherit'></box-icon>
                Materia Prima / Insumos
            </button>
        </div>

        <!-- ── Tab 1: Productos Terminados ───────────────────────────── -->
        <section class="table-card inv-tab-panel active" id="panel-productos">
            <header class="table-card__header">
                <h3 class="table-card__title">
                    <box-icon name='package' color='var(--color-primary)' size='sm' style="transform:translateY(3px);"></box-icon>
                    Stock de Productos Terminados
                </h3>
                <div id="stat-productos" style="display:flex; gap:8px; align-items:center; font-size:0.8rem; color:var(--color-text-muted);"></div>
            </header>
            <div class="table-wrap">
                <table class="data-table" id="tabla-inventario" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Mínimo</th>
                            <th>Estado</th>
                            <th>Última Actualización</th>
                            <th style="text-align:center;">Ajustar</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-inventario">
                        <tr>
                            <td colspan="7" style="text-align:center; padding:3rem;">
                                <div class="skeleton skeleton--row"></div>
                                <div class="skeleton skeleton--row"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Tab 2: Materia Prima / Insumos ────────────────────────── -->
        <section class="table-card inv-tab-panel" id="panel-materiaprima" style="display:none;">
            <header class="table-card__header">
                <h3 class="table-card__title">
                    <box-icon name='leaf' color='var(--color-primary)' size='sm' style="transform:translateY(3px);"></box-icon>
                    Stock de Materia Prima e Insumos
                </h3>
                <div id="stat-mp" style="display:flex; gap:8px; align-items:center; font-size:0.8rem; color:var(--color-text-muted);"></div>
            </header>
            <div class="table-wrap">
                <table class="data-table" id="tabla-mp" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Insumo / Materia Prima</th>
                            <th>Unidad de Medida</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Última Entrada</th>
                            <th>Estado</th>
                            <th style="text-align:center;">Ajustar</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-mp">
                        <tr>
                            <td colspan="7" style="text-align:center; padding:3rem;">
                                <div class="skeleton skeleton--row"></div>
                                <div class="skeleton skeleton--row"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </div><!-- /page-content -->
</main><!-- /admin-main -->

<!-- ═══════════════════════════════════════════════════════════════════
     Modal: Ajuste de Producto Terminado
     ═══════════════════════════════════════════════════════════════════ -->
<div class="admin-modal-overlay" id="modal-ajuste">
    <div class="admin-modal admin-modal--md">
        <header class="admin-modal__header">
            <h2 class="admin-modal__title" style="display:flex; align-items:center; gap:8px;">
                <box-icon name='slider' color='var(--color-primary)'></box-icon>
                Ajuste Manual — Producto
            </h2>
            <button class="admin-modal__close" data-accion="cerrar-modal-prod">✕</button>
        </header>

        <form id="form-ajuste" class="admin-modal__body admin-form" style="display:grid; grid-template-columns: 1fr; gap:1.2rem;">

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="ajuste-producto">
                    Producto <span class="required">*</span>
                </label>
                <select id="ajuste-producto" name="id_producto" class="form-select" required>
                    <option value="">Seleccione el producto...</option>
                    <?php foreach ($productos ?? [] as $prod): ?>
                        <option value="<?php echo htmlspecialchars($prod['id_producto']); ?>">
                            <?php echo htmlspecialchars($prod['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="ajuste-tipo">
                        Tipo de Movimiento <span class="required">*</span>
                    </label>
                    <select id="ajuste-tipo" name="tipo" class="form-select" required>
                        <option value="ENTRADA">⬆ Entrada (+)</option>
                        <option value="SALIDA">⬇ Salida (−)</option>
                        <option value="AJUSTE">⇔ Ajuste</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="ajuste-cant">
                        Cantidad <span class="required">*</span>
                    </label>
                    <input type="number" id="ajuste-cant" name="cantidad" class="form-control" min="1" step="1" required placeholder="0">
                </div>
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="ajuste-motivo">
                    Motivo <span class="required">*</span>
                </label>
                <input type="text" id="ajuste-motivo" name="motivo" class="form-control" required
                       placeholder="Ej: Mercancía dañada, Corrección de inventario...">
            </div>

        </form>

        <footer class="admin-modal__footer">
            <button class="btn--admin-secondary" data-accion="cerrar-modal-prod">Cancelar</button>
            <button class="btn--admin-primary" form="form-ajuste" type="submit" style="display:flex; align-items:center; gap:6px;">
                <box-icon name='save' color='inherit' size='sm'></box-icon> Guardar Movimiento
            </button>
        </footer>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     Modal: Ajuste de Materia Prima / Insumos
     ═══════════════════════════════════════════════════════════════════ -->
<div class="admin-modal-overlay" id="modal-ajuste-mp">
    <div class="admin-modal admin-modal--md">
        <header class="admin-modal__header">
            <h2 class="admin-modal__title" style="display:flex; align-items:center; gap:8px;">
                <box-icon name='leaf' color='var(--color-primary)'></box-icon>
                Ajuste Manual — Insumo
            </h2>
            <button class="admin-modal__close" data-accion="cerrar-modal-mp">✕</button>
        </header>

        <form id="form-ajuste-mp" class="admin-modal__body admin-form" style="display:grid; grid-template-columns: 1fr; gap:1.2rem;">

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="mp-materia">
                    Insumo / Materia Prima <span class="required">*</span>
                </label>
                <select id="mp-materia" name="id_materia" class="form-select" required>
                    <option value="">Seleccione el insumo...</option>
                    <?php foreach ($materias ?? [] as $mat): ?>
                        <option value="<?php echo htmlspecialchars($mat['id_materia']); ?>"
                                data-unidad="<?php echo htmlspecialchars($mat['unidad_medida']); ?>">
                            <?php echo htmlspecialchars($mat['nombre']); ?>
                            (<?php echo htmlspecialchars($mat['unidad_medida']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="mp-tipo">
                        Tipo de Movimiento <span class="required">*</span>
                    </label>
                    <select id="mp-tipo" name="tipo" class="form-select" required>
                        <option value="ENTRADA">⬆ Entrada (+)</option>
                        <option value="SALIDA">⬇ Salida (−)</option>
                        <option value="AJUSTE">⇔ Ajuste</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="mp-cant">
                        Cantidad <span class="required">*</span>
                    </label>
                    <div style="position:relative;">
                        <input type="number" id="mp-cant" name="cantidad" class="form-control" min="0.001" step="0.001" required placeholder="0.000">
                        <span id="mp-unidad-label" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-weight:600; font-size:0.8rem; pointer-events:none;"></span>
                    </div>
                </div>
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="mp-motivo">
                    Motivo <span class="required">*</span>
                </label>
                <input type="text" id="mp-motivo" name="motivo" class="form-control" required
                       placeholder="Ej: Compra de insumos, Consumo en producción...">
            </div>

        </form>

        <footer class="admin-modal__footer">
            <button class="btn--admin-secondary" data-accion="cerrar-modal-mp">Cancelar</button>
            <button class="btn--admin-primary" form="form-ajuste-mp" type="submit" style="display:flex; align-items:center; gap:6px;">
                <box-icon name='save' color='inherit' size='sm'></box-icon> Guardar Movimiento
            </button>
        </footer>
    </div>
</div>

<script>
    window.addEventListener('load', () => {
        const loader = document.getElementById('admin-loader');
        if(loader) loader.style.display = 'none';
    });
</script>

<!-- SweetAlert2 (local) -->
<script src="<?php echo BASE_URL; ?>assets/js/vendor/sweetalert2.min.js"></script>
<!-- jQuery + DataTables (local) -->
<script src="<?php echo BASE_URL; ?>assets/js/vendor/jquery.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/vendor/dataTables.min.js"></script>

<script type="module" src="<?php echo BASE_URL; ?>assets/js/modules/inventario.js?v=<?php echo time(); ?>"></script>

</body>
</html>
