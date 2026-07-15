<?php

namespace App\Controllers;

use App\Core\Database;
use App\Helpers\Helper;
use PDO;

$type = $_REQUEST['type'] ?? 'index';

if ($type === 'index') {

    // ─── Estadísticas del Dashboard ───────────────────────────────────────
    try {
        $db = Database::getConnection();

        // 1. Ventas del mes
        $sqlVentas = "SELECT COALESCE(SUM(total), 0) AS total_ventas
                      FROM pedido
                      WHERE estado IN ('CONFIRMADO', 'PROCESANDO', 'DESPACHADO', 'ENTREGADO')
                        AND MONTH(fecha_pedido) = MONTH(CURRENT_DATE())
                        AND YEAR(fecha_pedido)  = YEAR(CURRENT_DATE())";
        $ventasMes = $db->query($sqlVentas)->fetchColumn();

        // 2. Pedidos Pendientes
        $sqlPendientes = "SELECT COUNT(*) FROM pedido WHERE estado = 'PENDIENTE'";
        $pedidosPendientes = $db->query($sqlPendientes)->fetchColumn();

        // 3. Clientes Registrados
        $sqlClientes = "SELECT COUNT(*) FROM cliente WHERE estatus = 1";
        $totalClientes = $db->query($sqlClientes)->fetchColumn();

        // 4. Productos con bajo stock
        $sqlStockLow = "SELECT COUNT(*)
                        FROM inventario_producto
                        WHERE cantidad_actual <= cantidad_minima";
        $stockBajo = $db->query($sqlStockLow)->fetchColumn();

        // 5. Últimos pedidos (Top 5)
        $sqlUltimos = "SELECT p.id_pedido, p.numero_pedido, p.total, p.estado, p.fecha_pedido,
                              per.nombre, per.apellido
                       FROM pedido p
                       INNER JOIN persona per ON p.cedula_cliente = per.cedula
                       ORDER BY p.fecha_pedido DESC
                       LIMIT 5";
        $ultimosPedidos = $db->query($sqlUltimos)->fetchAll(PDO::FETCH_ASSOC);

    } catch (\Exception $e) {
        Helper::ErrorLog('Error en Dashboard: ' . $e->getMessage());
        $ventasMes = 0; $pedidosPendientes = 0; $totalClientes = 0; $stockBajo = 0;
        $ultimosPedidos = [];
    }

    $titulo = 'Dashboard — El Trigal Dorado';
    require_once BASE_PATH . '/resources/views/admin/dashboard.php';
}
