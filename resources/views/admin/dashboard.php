<?php require_once BASE_PATH . '/resources/views/layout/head-admin.php'; ?>
<?php require_once BASE_PATH . '/resources/views/layout/sidebar-admin.php'; ?>

    <div class="page-content">
        <!-- Header -->
        <header class="page-header">
            <div>
                <h1 class="page-header__title">Dashboard Principal</h1>
                <p class="page-header__subtitle">Métricas en tiempo real de El Trigal Dorado.</p>
            </div>
            <div>
                <button class="btn--admin-primary" onclick="window.location.reload();">
                    🔄 Actualizar Datos
                </button>
            </div>
        </header>

        <!-- Métricas KPI -->
        <section class="metrics-grid">
            <!-- Ventas -->
            <article class="metric-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <p class="metric-card__label">Ventas del Mes (Bs.)</p>
                        <p class="metric-card__value"><?php echo number_format($ventasMes ?? 0, 2); ?></p>
                    </div>
                    <div class="metric-card__icon metric-card__icon--success"><box-icon name='dollar-circle' type='solid' size='md'></box-icon></div>
                </div>
                <div class="metric-card__delta">
                    <span>↑ En curso</span>
                </div>
            </article>

            <!-- Pedidos Pendientes -->
            <article class="metric-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <p class="metric-card__label">Pedidos Pendientes</p>
                        <p class="metric-card__value"><?php echo number_format($pedidosPendientes ?? 0); ?></p>
                    </div>
                    <div class="metric-card__icon metric-card__icon--info"><box-icon name='cart' type='solid' size='md'></box-icon></div>
                </div>
                <div class="metric-card__delta">
                    <span style="color:var(--color-text-muted);">Requieren aprobación</span>
                </div>
            </article>

            <!-- Clientes -->
            <article class="metric-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <p class="metric-card__label">Clientes Activos</p>
                        <p class="metric-card__value"><?php echo number_format($totalClientes ?? 0); ?></p>
                    </div>
                    <div class="metric-card__icon"><box-icon name='group' type='solid' size='md'></box-icon></div>
                </div>
            </article>

            <!-- Alerta Stock -->
            <article class="metric-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <p class="metric-card__label">Alertas de Stock</p>
                        <p class="metric-card__value"><?php echo number_format($stockBajo ?? 0); ?></p>
                    </div>
                    <div class="metric-card__icon <?php echo ($stockBajo > 0) ? 'metric-card__icon--danger' : 'metric-card__icon--success'; ?>">
                        <box-icon name='error-circle' type='solid' size='md'></box-icon>
                    </div>
                </div>
                <?php if (($stockBajo ?? 0) > 0): ?>
                    <div class="metric-card__delta metric-card__delta--down">
                        <span>Requiere producción</span>
                    </div>
                <?php else: ?>
                    <div class="metric-card__delta">
                        <span>Inventario saludable</span>
                    </div>
                <?php endif; ?>
            </article>
        </section>

        <!-- Tablas de Resumen -->
        <section class="form-row">
            <!-- Últimos Pedidos -->
            <div class="table-card" style="grid-column: 1 / -1;">
                <header class="table-card__header">
                    <h3 class="table-card__title">Últimos Pedidos</h3>
                    <a href="<?php echo BASE_URL; ?>?page=Pedido" class="btn--admin-secondary" style="padding:6px 12px; font-size:0.75rem;">Ver Todos</a>
                </header>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>N° Pedido</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ultimosPedidos)): ?>
                                <?php foreach ($ultimosPedidos as $ped):
                                    $claseBadge = strtolower($ped['estado']);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ped['numero_pedido']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ped['nombre'] . ' ' . $ped['apellido']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($ped['fecha_pedido'])); ?></td>
                                    <td><strong>Bs. <?php echo number_format($ped['total'], 2); ?></strong></td>
                                    <td><span class="badge badge--<?php echo $claseBadge; ?>"><?php echo htmlspecialchars($ped['estado']); ?></span></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>?page=Pedido&type=ver&id=<?php echo urlencode($ped['id_pedido']); ?>" class="btn-action btn-action--view" aria-label="Ver detalle">
                                            <box-icon name='show' size='xs' color='#fff'></box-icon>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; padding:2rem;">No hay pedidos recientes.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </div><!-- /page-content -->

</main><!-- /admin-main -->

<script>
    // Remover skeleton loader
    window.addEventListener('load', () => {
        document.getElementById('admin-loader').style.display = 'none';
    });
</script>

</body>
</html>
