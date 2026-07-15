<?php
$paginaActual = $_REQUEST['page'] ?? 'Dashboard';
$usuario = $_SESSION['usuario'] ?? [];
$permisos = $usuario['permisos'] ?? [];

function isActive($pagina, $actual) {
    return (strtolower($pagina) === strtolower($actual)) ? 'active' : '';
}

function can($permiso, $permisos) {
    return in_array($permiso, $permisos, true);
}
?>

<aside class="sidebar" id="sidebar">
    <header class="sidebar__header">
        <img class="sidebar__logo" src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo">
        <div class="sidebar__brand">
            <p class="sidebar__brand-name">El Trigal Dorado</p>
            <p class="sidebar__brand-sub">Panel TPS</p>
        </div>
        <button class="sidebar__toggle" id="btn-toggle-sidebar" aria-label="Alternar menú">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </header>

    <nav class="sidebar__nav">
        <!-- Dashboard general -->
        <p class="sidebar__section-label">General</p>
        <a href="<?php echo BASE_URL; ?>?page=Dashboard"
           class="sidebar__link <?php echo isActive('Dashboard', $paginaActual); ?>"
           data-tooltip="Dashboard">
            <span class="sidebar__link-icon"><box-icon name='bar-chart-alt-2' size='sm' animation='tada-hover'></box-icon></span>
            <span class="sidebar__link-text">Dashboard</span>
        </a>

        <!-- Módulo: Pedidos (Transaccional) -->
        <?php if (can('PERM_PEDIDO_VER', $permisos) || can('PERM_PEDIDO_CREAR', $permisos)): ?>
        <p class="sidebar__section-label">Transaccional</p>
        <a href="<?php echo BASE_URL; ?>?page=Pedido"
           class="sidebar__link <?php echo isActive('Pedido', $paginaActual); ?>"
           data-tooltip="Gestión de Pedidos">
            <span class="sidebar__link-icon"><box-icon name='cart' size='sm' animation='tada-hover'></box-icon></span>
            <span class="sidebar__link-text">Gestión de Pedidos</span>
        </a>
        <?php endif; ?>

        <!-- Módulo: Clientes -->
        <?php if (can('PERM_CLIENTE_VER', $permisos)): ?>
        <a href="<?php echo BASE_URL; ?>?page=Cliente"
           class="sidebar__link <?php echo isActive('Cliente', $paginaActual); ?>"
           data-tooltip="Clientes">
            <span class="sidebar__link-icon"><box-icon name='group' size='sm' animation='tada-hover'></box-icon></span>
            <span class="sidebar__link-text">Clientes</span>
        </a>
        <?php endif; ?>

        <!-- Módulo: Inventario y Catálogo -->
        <?php if (can('PERM_PROD_VER', $permisos) || can('PERM_INV_VER', $permisos)): ?>
        <p class="sidebar__section-label">Operaciones</p>

        <?php if (can('PERM_PROD_VER', $permisos)): ?>
        <a href="<?php echo BASE_URL; ?>?page=Producto"
           class="sidebar__link <?php echo isActive('Producto', $paginaActual); ?>"
           data-tooltip="Catálogo de Productos">
            <span class="sidebar__link-icon"><box-icon name='store-alt' size='sm' animation='tada-hover'></box-icon></span>
            <span class="sidebar__link-text">Catálogo</span>
        </a>
        <?php endif; ?>

        <?php if (can('PERM_INV_VER', $permisos)): ?>
        <a href="<?php echo BASE_URL; ?>?page=Inventario"
           class="sidebar__link <?php echo isActive('Inventario', $paginaActual); ?>"
           data-tooltip="Inventario (ACID)">
            <span class="sidebar__link-icon"><box-icon name='package' size='sm' animation='tada-hover'></box-icon></span>
            <span class="sidebar__link-text">Inventario</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Módulo: Sistema / Seguridad -->
        <?php if (can('PERM_USUARIO_GESTIONAR', $permisos) || can('PERM_ROL_GESTIONAR', $permisos)): ?>
        <p class="sidebar__section-label">Sistema</p>

        <?php if (can('PERM_USUARIO_GESTIONAR', $permisos)): ?>
        <a href="<?php echo BASE_URL; ?>?page=Usuario"
           class="sidebar__link <?php echo isActive('Usuario', $paginaActual); ?>"
           data-tooltip="Usuarios">
            <span class="sidebar__link-icon"><box-icon name='user-pin' size='sm' animation='tada-hover'></box-icon></span>
            <span class="sidebar__link-text">Usuarios</span>
        </a>
        <?php endif; ?>

        <?php if (can('PERM_ROL_GESTIONAR', $permisos)): ?>
        <a href="<?php echo BASE_URL; ?>?page=Rol"
           class="sidebar__link <?php echo isActive('Rol', $paginaActual); ?>"
           data-tooltip="Roles y Permisos">
            <span class="sidebar__link-icon"><box-icon name='shield' size='sm' animation='tada-hover'></box-icon></span>
            <span class="sidebar__link-text">Roles y Permisos</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>

    <footer class="sidebar__footer">
        <div class="sidebar__user-avatar">
            <?php echo strtoupper(substr($usuario['nombre'] ?? 'U', 0, 1)); ?>
        </div>
        <div class="sidebar__user-info">
            <p class="sidebar__user-name"><?php echo htmlspecialchars($usuario['nombre'] ?? 'Usuario'); ?></p>
            <p class="sidebar__user-role"><?php echo htmlspecialchars($usuario['rol'] ?? 'Sin rol'); ?></p>
        </div>
    </footer>
</aside>

<!-- Topbar móvil overlay -->
<div class="admin-modal-overlay" id="sidebar-overlay" style="z-index: 90;" onclick="document.getElementById('sidebar').classList.remove('mobile-open'); this.classList.remove('open');"></div>

<!-- MAIN CONTENT WRAPPER (Inicia aquí, cierra en las vistas) -->
<main class="admin-main">
    <!-- Topbar (navbar horizontal) -->
    <header class="topbar">
        <div class="topbar__breadcrumb">
            <span class="topbar__breadcrumb-item">TPS</span>
            <span class="topbar__breadcrumb-sep">/</span>
            <span class="topbar__breadcrumb-item"><?php echo htmlspecialchars($paginaActual); ?></span>
        </div>

        <div class="topbar__actions">
            <!-- Botón toggle móvil -->
            <button class="btn-action d-md-none" id="btn-mobile-sidebar" style="background:#fff; border:1px solid #ddd; display:flex; align-items:center; justify-content:center;" aria-label="Abrir menú">
                <box-icon name='menu' size='sm'></box-icon>
            </button>
            <a href="<?php echo BASE_URL; ?>" class="btn--admin-secondary" style="display:flex; align-items:center; gap:5px;">
                <box-icon name='globe' size='xs' animation='spin-hover'></box-icon> Ver Tienda
            </a>
            <a href="<?php echo BASE_URL; ?>?page=Logout" class="btn--admin-danger">
                Salir
            </a>
        </div>
    </header>

    <!-- JS Básico para el sidebar -->
    <script>
        document.getElementById('btn-toggle-sidebar')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });
        document.getElementById('btn-mobile-sidebar')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.add('mobile-open');
            document.getElementById('sidebar-overlay').classList.add('open');
        });
    </script>
