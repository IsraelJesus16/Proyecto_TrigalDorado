<?php require_once BASE_PATH . '/resources/views/layout/head-admin.php'; ?>
<?php require_once BASE_PATH . '/resources/views/layout/sidebar-admin.php'; ?>

    <div class="page-content">
        <header class="page-header">
            <div>
                <h1 class="page-header__title">Roles y Permisos</h1>
                <p class="page-header__subtitle">Gestión del control de acceso basado en roles (RBAC).</p>
            </div>
            <div>
                <button class="btn--admin-primary" id="btn-nuevo-rol" style="display:flex;align-items:center;gap:6px;">
                    <box-icon name='plus-circle' size='sm' color='#fff' animation='tada-hover'></box-icon> Nuevo Rol
                </button>
            </div>
        </header>

        <section class="table-card">
            <header class="table-card__header">
                <h3 class="table-card__title">Listado de Roles</h3>
            </header>
            <div class="table-wrap">
                <table class="data-table" id="tabla-roles">
                    <thead>
                        <tr>
                            <th>ID Rol</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles ?? [] as $rol): ?>
                        <tr>
                            <td>
                                <span class="badge" style="background:rgba(181,142,58,0.1);color:var(--color-primary-dark);border:1px solid rgba(181,142,58,0.2);">
                                    <box-icon name='shield' size='xs' color='inherit' style='transform:translateY(2px);margin-right:3px;'></box-icon>
                                    <?php echo htmlspecialchars($rol['id_rol']); ?>
                                </span>
                            </td>
                            <td><strong style="color:var(--color-secondary);"><?php echo htmlspecialchars($rol['nombre']); ?></strong></td>
                            <td style="color:var(--color-text-muted);font-size:0.875rem;"><?php echo htmlspecialchars($rol['descripcion']); ?></td>
                            <td style="text-align:center;">
                                <button class="btn-action btn-action--edit"
                                        data-accion="editar"
                                        data-id="<?php echo htmlspecialchars($rol['id_rol']); ?>"
                                        aria-label="Editar" title="Editar Rol">
                                    <box-icon name='edit' size='xs' animation='tada-hover' color='var(--color-warning)'></box-icon>
                                </button>

                                <?php if ($rol['id_rol'] !== 'ROL_SUPERADMIN'): ?>
                                <button class="btn-action btn-action--delete"
                                        data-accion="eliminar"
                                        data-id="<?php echo htmlspecialchars($rol['id_rol']); ?>"
                                        aria-label="Eliminar" title="Eliminar Rol">
                                    <box-icon name='trash' size='xs' animation='tada-hover' color='var(--color-danger)'></box-icon>
                                </button>
                                <?php else: ?>
                                <span title="Rol protegido del sistema" style="display:inline-flex;align-items:center;gap:4px;
                                      font-size:0.75rem;color:var(--color-text-muted);padding:4px 8px;
                                      border:1px dashed var(--border-color);border-radius:20px;">
                                    <box-icon name='lock' size='xs' color='var(--color-text-muted)'></box-icon>
                                    Protegido
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div><!-- /page-content -->
</main><!-- /admin-main -->

<!-- Estilos específicos para la vista de Roles -->
<style>
    .permiso-card {
        background: var(--bg-body);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: var(--space-md);
        transition: all 0.2s ease;
    }
    .permiso-card:hover {
        border-color: var(--color-primary);
        box-shadow: 0 4px 12px rgba(181, 142, 58, 0.1);
    }
    .permiso-card__header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: var(--space-sm);
        padding-bottom: 8px;
        border-bottom: 1px solid var(--border-color);
        color: var(--color-primary-dark);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    /* Toggle Switch */
    .form-switch {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 6px 4px;
        border-radius: var(--radius-sm);
        transition: background 0.2s;
    }
    .form-switch:hover {
        background: rgba(0,0,0,0.02);
    }
    .form-switch input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 36px;
        height: 20px;
        background: var(--color-text-muted);
        border-radius: 20px;
        position: relative;
        cursor: pointer;
        outline: none;
        transition: background 0.3s ease;
        flex-shrink: 0;
    }
    .form-switch input[type="checkbox"]::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 16px;
        height: 16px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .form-switch input[type="checkbox"]:checked {
        background: var(--color-primary);
    }
    .form-switch input[type="checkbox"]:checked::after {
        transform: translateX(16px);
    }
    .form-switch__label {
        font-size: 0.875rem;
        color: var(--color-secondary);
        user-select: none;
    }
</style>

<!-- Modal para Crear/Editar Rol -->
<div class="admin-modal-overlay" id="modal-rol">
    <div class="admin-modal admin-modal--lg">
        <header class="admin-modal__header">
            <h2 class="admin-modal__title" id="modal-rol-titulo">Nuevo Rol</h2>
            <button class="admin-modal__close" data-accion="cerrar-modal">✕</button>
        </header>

        <div class="admin-modal__body">
            <div class="business-alert business-alert--info visible" style="margin-bottom: var(--space-xl); display:flex; align-items:center; gap:10px;">
                <box-icon name='info-circle' size='sm' color='var(--color-info)'></box-icon>
                <span>Los roles determinan qué módulos puede ver y modificar un usuario en el sistema.</span>
            </div>

            <form id="form-rol" class="admin-form" novalidate>
                <input type="hidden" id="id_rol_viejo" name="id_rol_viejo" value="">

                <div class="admin-form__section">
                    <h3 class="admin-form__section-title">Datos Básicos</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="id_rol">ID del Rol <span class="required">*</span></label>
                            <input class="form-control" type="text" id="id_rol" name="id_rol"
                                   placeholder="Ej: GERENTE (se añadirá ROL_)" required>
                            <div class="form-hint">Identificador único (sin espacios).</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre a Mostrar <span class="required">*</span></label>
                            <input class="form-control" type="text" id="nombre" name="nombre"
                                   placeholder="Ej: Gerente General" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:var(--space-md);">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <input class="form-control" type="text" id="descripcion" name="descripcion"
                               placeholder="Breve descripción de las responsabilidades">
                    </div>
                </div>

                <div class="admin-form__section">
                    <h3 class="admin-form__section-title">Asignación de Permisos</h3>

                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-md);">
                        <?php 
                        $iconosModulo = [
                            'clientes' => 'group',
                            'inventario' => 'box',
                            'pedidos' => 'receipt',
                            'productos' => 'shopping-bag',
                            'reportes' => 'bar-chart-alt-2',
                            'roles' => 'shield-quarter',
                            'usuarios' => 'user-badge'
                        ];
                        foreach ($permisosPorModulo ?? [] as $modulo => $permisosM): 
                            $icono = $iconosModulo[strtolower($modulo)] ?? 'folder';
                        ?>
                            <div class="permiso-card">
                                <header class="permiso-card__header">
                                    <box-icon name="<?php echo $icono; ?>" size="sm" color="var(--color-primary)"></box-icon>
                                    <?php echo htmlspecialchars($modulo); ?>
                                </header>
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <?php foreach ($permisosM as $permiso): ?>
                                        <label class="form-switch">
                                            <input type="checkbox" name="permisos[]" value="<?php echo htmlspecialchars($permiso['id_permiso']); ?>">
                                            <span class="form-switch__label"><?php echo htmlspecialchars($permiso['descripcion']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>

        <footer class="admin-modal__footer">
            <button class="btn--admin-secondary" data-accion="cerrar-modal">Cancelar</button>
            <button class="btn--admin-primary" form="form-rol" id="btn-guardar-rol" style="display:flex;align-items:center;gap:6px;">
                <box-icon name='save' size='sm' color='#fff' animation='tada-hover'></box-icon> Guardar Rol
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

<!-- Scripts Locales -->
<script src="<?php echo BASE_URL; ?>assets/js/vendor/sweetalert2.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/vendor/jquery.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/vendor/dataTables.min.js"></script>
<script>
    // Inicializar DataTable para Roles
    $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#tabla-roles')) {
            $('#tabla-roles').DataTable({
                language: { url: window.BASE_URL + 'assets/js/vendor/es-ES.json' },
                columnDefs: [{ orderable: false, targets: 3 }] // La columna de acciones no se ordena
            });
        }
    });
</script>

<script type="module" src="<?php echo BASE_URL; ?>assets/js/modules/roles.js?v=<?php echo time(); ?>"></script>

</body>
</html>
