<?php
namespace App\Controllers;

use App\Models\Inventario;
use App\Core\Database;

$action = $_REQUEST['action'] ?? null;
$type   = $_REQUEST['type']   ?? 'index';

// ═══════════════════════════════════════════════════════════════════
// AJAX: Peticiones de datos e interacción
// ═══════════════════════════════════════════════════════════════════
if ($action) {
    header('Content-Type: application/json');
    $inv = new Inventario();

    // ── Consultar stock de productos terminados ──────────────────────
    if ($action === 'consultar') {
        echo json_encode($inv->Transaccion(['peticion' => 'consultar']));
        return;
    }

    // ── Ajuste manual de producto terminado ─────────────────────────
    if ($action === 'ajustar') {
        $userCedula = $_SESSION['usuario']['cedula'] ?? 'V-00000000';
        echo json_encode($inv->Transaccion([
            'peticion'       => 'ajustar',
            'id_producto'    => trim($_POST['id_producto'] ?? ''),
            'cantidad'       => (float)($_POST['cantidad'] ?? 0),
            'tipo'           => $_POST['tipo'] ?? 'ENTRADA',
            'motivo'         => trim($_POST['motivo'] ?? 'Ajuste manual'),
            'cedula_usuario' => $userCedula
        ]));
        return;
    }

    // ── Consultar stock de materia prima ─────────────────────────────
    if ($action === 'consultar_mp') {
        echo json_encode($inv->Transaccion(['peticion' => 'consultar_mp']));
        return;
    }

    // ── Ajuste manual de materia prima ───────────────────────────────
    if ($action === 'ajustar_mp') {
        $userCedula = $_SESSION['usuario']['cedula'] ?? 'V-00000000';
        echo json_encode($inv->Transaccion([
            'peticion'       => 'ajustar_mp',
            'id_materia'     => trim($_POST['id_materia'] ?? ''),
            'cantidad'       => (float)($_POST['cantidad'] ?? 0),
            'tipo'           => $_POST['tipo'] ?? 'ENTRADA',
            'motivo'         => trim($_POST['motivo'] ?? 'Ajuste manual'),
            'cedula_usuario' => $userCedula
        ]));
        return;
    }

    echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'Acción no válida']]);
    return;
}

// ═══════════════════════════════════════════════════════════════════
// VIEW: Renderizar la página
// ═══════════════════════════════════════════════════════════════════
if ($type === 'index') {
    $titulo = 'Gestión de Inventario — El Trigal Dorado';

    $db = new Database();
    $db->LlamarConexion();

    // Select de productos para el modal de ajuste de productos terminados
    $stmtP = $db->LlamarConexion()->query(
        "SELECT id_producto, nombre FROM producto WHERE estatus = 1 ORDER BY nombre"
    );
    $productos = $stmtP->fetchAll(\PDO::FETCH_ASSOC);

    // Select de materias primas para el modal de ajuste de insumos
    $stmtM = $db->LlamarConexion()->query(
        "SELECT id_materia, nombre, unidad_medida FROM materia_prima WHERE estatus = 1 ORDER BY nombre"
    );
    $materias = $stmtM->fetchAll(\PDO::FETCH_ASSOC);

    $db->DestruirConexion();

    require_once BASE_PATH . '/resources/views/admin/inventario/index.php';
    return;
}
