<?php require_once BASE_PATH . '/resources/views/layout/head-admin.php'; ?>
<?php require_once BASE_PATH . '/resources/views/layout/sidebar-admin.php'; ?>


    <div class="page-content">
        <header class="page-header">
            <div>
                <h1 class="page-header__title">Directorio de Clientes</h1>
                <p class="page-header__subtitle">Gestión de cartera B2C y B2B, condiciones comerciales y crédito.</p>
            </div>
            <div>
                <button class="btn--admin-primary" id="btn-nuevo-cliente" style="display:flex; align-items:center; gap:5px;">
                    <box-icon name='plus' color='inherit' size='sm'></box-icon> Nuevo Cliente
                </button>
            </div>
        </header>

        <section class="table-card">
            <header class="table-card__header">
                <h3 class="table-card__title">Clientes Registrados</h3>
            </header>
            <div class="table-wrap">
                <table class="data-table" id="tabla-clientes">
                    <thead>
                        <tr>
                            <th>Identificación</th>
                            <th>Nombre / Razón Social</th>
                            <th>Contacto</th>
                            <th>Tipo</th>
                            <th>Condición</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-clientes">
                        <!-- AJAX content -->
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

<!-- Modal Cliente -->
<div class="admin-modal-overlay" id="modal-cliente">
    <div class="admin-modal admin-modal--lg">
        <header class="admin-modal__header">
            <h2 class="admin-modal__title" id="modal-titulo">Registro de Cliente</h2>
            <button class="admin-modal__close" data-accion="cerrar-modal">✕</button>
        </header>

        <form id="form-cliente" class="admin-modal__body admin-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
            <input type="hidden" name="es_edicion" id="cli-es-edicion" value="">
            
            <!-- Datos Personales -->
            <div class="form-section-header" style="grid-column: 1 / -1; margin-bottom: 0.5rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem;">
                <h4 style="color: var(--color-primary); margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;"><box-icon name='user' color='inherit' size='sm'></box-icon> Datos Personales</h4>
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-cedula-num">Cédula de Identidad <span class="required">*</span></label>
                <div style="display: flex; gap: 0.5rem;">
                    <select id="cli-cedula-prefijo" class="form-select" style="width: 80px; padding: 0.6rem 0.5rem;">
                        <option value="V-">V-</option>
                        <option value="E-">E-</option>
                        <option value="J-">J-</option>
                        <option value="G-">G-</option>
                    </select>
                    <input type="text" id="cli-cedula-num" class="form-control" required placeholder="12345678" maxlength="9" pattern="[0-9]+">
                    <input type="hidden" id="cli-cedula" name="cedula" value="">
                </div>
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-correo">Correo Electrónico <span class="required">*</span></label>
                <input type="email" id="cli-correo" name="correo" class="form-control" required placeholder="ejemplo@correo.com">
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-nombre">Nombre <span class="required">*</span></label>
                <input type="text" id="cli-nombre" name="nombre" class="form-control" required maxlength="80" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+">
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-apellido">Apellido <span class="required">*</span></label>
                <input type="text" id="cli-apellido" name="apellido" class="form-control" required maxlength="80" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+">
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-telefono">Teléfono</label>
                <input type="text" id="cli-telefono" name="telefono" class="form-control" placeholder="0414-0000000" maxlength="12">
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-direccion">Dirección Física</label>
                <textarea id="cli-direccion" name="direccion" class="form-control" rows="1" placeholder="Dirección completa"></textarea>
            </div>

            <!-- Datos Comerciales -->
            <div class="form-section-header" style="grid-column: 1 / -1; margin-top: 1rem; margin-bottom: 0.5rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem;">
                <h4 style="color: var(--color-primary); margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;"><box-icon name='briefcase' color='inherit' size='sm'></box-icon> Datos Comerciales (B2B)</h4>
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-tipo">Tipo de Cliente <span class="required">*</span></label>
                <select id="cli-tipo" name="tipo_cliente" class="form-select" required style="padding: 0.6rem 1rem;">
                    <option value="NATURAL">Natural</option>
                    <option value="JURIDICO">Jurídico</option>
                </select>
            </div>

            <div class="form-group b2b-field" style="display:none; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-rif-num">RIF <span class="required">*</span></label>
                <div style="display:flex; gap:0.5rem;">
                    <select id="cli-rif-prefijo" class="form-select" style="width: 80px; padding: 0.6rem 0.5rem;">
                        <option value="J-">J-</option>
                        <option value="V-">V-</option>
                        <option value="G-">G-</option>
                        <option value="E-">E-</option>
                        <option value="C-">C-</option>
                    </select>
                    <input type="text" id="cli-rif-num" class="form-control" placeholder="12345678-9" style="flex:1;" maxlength="10">
                    <input type="hidden" id="cli-rif" name="rif">
                </div>
            </div>

            <div class="form-group b2b-field" style="display:none; grid-column: 1 / -1; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-razon-social">Razón Social <span class="required">*</span></label>
                <input type="text" id="cli-razon-social" name="razon_social" class="form-control" placeholder="Nombre legal de la empresa">
            </div>

            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-condicion">Condición de Pago <span class="required">*</span></label>
                <select id="cli-condicion" name="condicion_pago" class="form-select" required style="padding: 0.6rem 1rem;">
                    <option value="CONTADO">Contado</option>
                    <option value="CREDITO_15">Crédito 15 Días</option>
                    <option value="CREDITO_30">Crédito 30 Días</option>
                    <option value="CREDITO_45">Crédito 45 Días</option>
                </select>
            </div>

            <div class="form-group credito-field" style="display:none; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="cli-limite">Límite de Crédito (Bs.) <span class="required">*</span></label>
                <input type="number" step="0.01" id="cli-limite" name="limite_credito" class="form-control" value="0" style="font-weight:bold; color:var(--color-primary);">
            </div>

        </form>

        <footer class="admin-modal__footer" id="modal-footer">
            <button class="btn--admin-secondary" data-accion="cerrar-modal">Cancelar</button>
            <button class="btn--admin-primary" form="form-cliente" type="submit">Guardar Cliente</button>
        </footer>
    </div>
</div>

    <!-- Modal de Detalles del Cliente -->
    <div class="admin-modal-overlay" id="modal-ver-cliente">
        <div class="admin-modal" style="max-width: 700px;">
            <header class="admin-modal__header">
                <h3 class="admin-modal__title" id="ver-modal-titulo"><box-icon name='id-card' color='var(--color-primary)'></box-icon> Detalles del Cliente</h3>
                <button class="admin-modal__close" data-accion="cerrar-modal-ver">✕</button>
            </header>
            
            <div class="admin-modal__body" id="ver-cliente-body" style="padding: 2rem;">
                <!-- Contenido generado dinámicamente en JS -->
            </div>
            
            <footer class="admin-modal__footer">
                <button class="btn--admin-secondary" data-accion="cerrar-modal-ver">Cerrar</button>
            </footer>
        </div>
    </div>

<script>
    window.addEventListener('load', () => {
        const loader = document.getElementById('admin-loader');
        if(loader) loader.style.display = 'none';
    });
</script>

<!-- jQuery + DataTables (local) -->
<script src="<?php echo BASE_URL; ?>assets/js/vendor/jquery.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/vendor/dataTables.min.js"></script>
<!-- SweetAlert2 (local) -->
<script src="<?php echo BASE_URL; ?>assets/js/vendor/sweetalert2.min.js"></script>
<script type="module" src="<?php echo BASE_URL; ?>assets/js/modules/clientes.js"></script>

</body>
</html>
