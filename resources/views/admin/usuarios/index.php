<?php require_once BASE_PATH . '/resources/views/layout/head-admin.php'; ?>
<?php require_once BASE_PATH . '/resources/views/layout/sidebar-admin.php'; ?>

<div class="page-content">

    <!-- ═══════════════════════════════════════════════════════
         CABECERA
    ═══════════════════════════════════════════════════════ -->
    <header class="page-header">
        <div>
            <h1 class="page-header__title" style="display:flex; align-items:center; gap:10px;">
                <box-icon name='group' color='var(--color-primary)' size='md'></box-icon>
                Gestión de Usuarios
            </h1>
            <p class="page-header__subtitle">Administración de accesos, credenciales y asignación de roles del sistema.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn--admin-primary" id="btn-nuevo-usuario" style="display:flex; align-items:center; gap:8px;">
                <box-icon name='user-plus' color='inherit' size='sm'></box-icon>
                Nuevo Usuario
            </button>
        </div>
    </header>

    <!-- ═══════════════════════════════════════════════════════
         TARJETA CON TABLA
    ═══════════════════════════════════════════════════════ -->
    <section class="table-card">
        <header class="table-card__header">
            <h3 class="table-card__title" style="display:flex; align-items:center; gap:8px;">
                <box-icon name='shield' color='var(--color-primary)' size='sm'></box-icon>
                Personal del Sistema
            </h3>
            <div id="stat-usuarios" style="font-size:0.85rem;"></div>
        </header>
        <div class="table-wrap">
            <table class="data-table" id="tabla-usuarios" style="width:100%;">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Rol Asignado</th>
                        <th>Último Acceso</th>
                        <th>Estado</th>
                        <th style="text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-usuarios">
                    <tr>
                        <td colspan="6" style="text-align:center; padding:3rem;">
                            <div class="skeleton skeleton--row"></div>
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
     Modal: Registro / Edición de Usuario
     ═══════════════════════════════════════════════════════════════════ -->
<div class="admin-modal-overlay" id="modal-usuario">
    <div class="admin-modal admin-modal--md">
        <header class="admin-modal__header">
            <h2 class="admin-modal__title" id="modal-titulo-usuario" style="display:flex; align-items:center; gap:8px;">
                <box-icon name='user-plus' color='var(--color-primary)'></box-icon>
                Nuevo Usuario
            </h2>
            <button class="admin-modal__close" data-accion="cerrar-modal-usuario">✕</button>
        </header>

        <form id="form-usuario" class="admin-modal__body admin-form" style="display:grid; grid-template-columns:1fr; gap:1.2rem;">
            <input type="hidden" name="id_usuario" id="usu-id">

            <!-- Cédula y Rol -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="usu-cedula">
                        Cédula <span class="required">*</span>
                    </label>
                    <input type="text" id="usu-cedula" name="cedula" class="form-control" required
                           placeholder="V-12345678">
                </div>
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="usu-rol">
                        Rol <span class="required">*</span>
                    </label>
                    <select id="usu-rol" name="id_rol" class="form-select" required>
                        <option value="">Seleccione un rol...</option>
                        <?php foreach ($roles ?? [] as $rol): ?>
                            <option value="<?php echo htmlspecialchars($rol['id_rol']); ?>">
                                <?php echo htmlspecialchars($rol['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Nombre y Apellido -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="usu-nombre">
                        Nombre <span class="required">*</span>
                    </label>
                    <input type="text" id="usu-nombre" name="nombre" class="form-control" required placeholder="Ej: Juan">
                </div>
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="usu-apellido">
                        Apellido <span class="required">*</span>
                    </label>
                    <input type="text" id="usu-apellido" name="apellido" class="form-control" required placeholder="Ej: Pérez">
                </div>
            </div>

            <!-- Correo -->
            <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                <label class="form-label" for="usu-correo">Correo Electrónico</label>
                <input type="email" id="usu-correo" name="correo" class="form-control"
                       placeholder="correo@ejemplo.com">
            </div>

            <!-- Username y Contraseña -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; border-top:1px solid var(--border-color); padding-top:1rem; margin-top:0.2rem;">
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="usu-username">
                        Nombre de Usuario <span class="required">*</span>
                    </label>
                    <input type="text" id="usu-username" name="username" class="form-control"
                           required autocomplete="new-username" placeholder="Ej: juan.perez">
                </div>
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="usu-password">
                        Contraseña
                        <span id="hint-pwd" style="font-size:0.75rem; font-weight:normal; color:var(--color-text-muted);">(Opcional en edición)</span>
                    </label>
                    <div style="position:relative;">
                        <input type="password" id="usu-password" name="password" class="form-control"
                               autocomplete="new-password" placeholder="••••••••"
                               style="padding-right:40px;">
                        <button type="button" id="btn-toggle-pwd" aria-label="Mostrar contraseña"
                                style="position:absolute; right:10px; top:50%; transform:translateY(-50%);
                                       background:none; border:none; cursor:pointer; display:flex;
                                       align-items:center; padding:0;">
                            <box-icon name='show' color='var(--color-text-muted)' size='sm'></box-icon>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <footer class="admin-modal__footer">
            <button class="btn--admin-secondary" data-accion="cerrar-modal-usuario">Cancelar</button>
            <button class="btn--admin-primary" form="form-usuario" type="submit"
                    style="display:flex; align-items:center; gap:6px;">
                <box-icon name='save' color='inherit' size='sm'></box-icon>
                Guardar Usuario
            </button>
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
<script src="<?php echo BASE_URL; ?>assets/js/vendor/sweetalert2.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/vendor/jquery.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/vendor/dataTables.min.js"></script>
<script type="module" src="<?php echo BASE_URL; ?>assets/js/modules/usuarios.js?v=<?php echo time(); ?>"></script>

<!-- Toggle mostrar/ocultar contraseña -->
<script>
document.getElementById('btn-toggle-pwd')?.addEventListener('click', function() {
    const p = document.getElementById('usu-password');
    const isPass = p.type === 'password';
    p.type = isPass ? 'text' : 'password';
    this.innerHTML = isPass
        ? '<box-icon name="hide" color="var(--color-text-muted)" size="sm"></box-icon>'
        : '<box-icon name="show" color="var(--color-text-muted)" size="sm"></box-icon>';
});
</script>

</body>
</html>
