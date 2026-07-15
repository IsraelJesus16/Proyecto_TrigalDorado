<?php

namespace App\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Helpers\Helper;

$type = $_REQUEST['type'] ?? 'index';

if ($type === 'index') {
    $titulo = 'Gestión de Pedidos — El Trigal Dorado';
    
    $pedidoModel = new Pedido();
    $resultado = $pedidoModel->Transaccion(['peticion' => 'listar']);
    $pedidos = $resultado['response']['data'] ?? [];

    require_once BASE_PATH . '/resources/views/admin/pedidos/index.php';
    return;
}

// ── API AJAX — Todas las peticiones POST ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['peticion'])) {
    header('Content-Type: application/json');

    $peticion = $_POST['peticion'];

    // ─────────────────────────────────────────────────────────────────
    // REGLA 1: Verificar morosidad del cliente antes del pedido
    // ─────────────────────────────────────────────────────────────────
    if ($peticion === 'verificar_morosidad') {
        $cedula = trim($_POST['cedula_cliente'] ?? '');

        if (empty($cedula)) {
            http_response_code(400);
            echo json_encode(['resultado' => 400, 'mensaje' => 'Cédula del cliente requerida.']);
            exit;
        }

        $clienteModel = new Cliente();
        $resultado    = $clienteModel->Transaccion([
            'peticion' => 'verificar_morosidad',
            'cedula'   => $cedula,
        ]);

        http_response_code($resultado['HTTP_STATUS']['codigo']);
        echo json_encode($resultado['response'] + ['bloqueado' => $resultado['bloqueado'] ?? false]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // REGLA 2: Verificar historial antes de asignar crédito
    // ─────────────────────────────────────────────────────────────────
    if ($peticion === 'verificar_historial') {
        $cedula = trim($_POST['cedula_cliente'] ?? '');

        $clienteModel = new Cliente();
        $resultado    = $clienteModel->Transaccion([
            'peticion' => 'conteo_transacciones',
            'cedula'   => $cedula,
        ]);

        // Si está en probación y el tipo de pago solicitado es crédito → bloquear
        $condicionPago     = strtoupper(trim($_POST['condicion_pago'] ?? 'CONTADO'));
        $enProbacion       = $resultado['en_probacion'] ?? false;
        $intentaCredito    = $condicionPago === 'CREDITO';
        $bloqueadoPorNuevo = $enProbacion && $intentaCredito;

        $response = $resultado['response'];
        $response['en_probacion']       = $enProbacion;
        $response['bloqueado_por_nuevo'] = $bloqueadoPorNuevo;
        $response['total_transacciones'] = $resultado['total'] ?? 0;

        if ($bloqueadoPorNuevo) {
            $response['resultado'] = 403;
            $response['icon']      = 'warning';
            $response['mensaje']   = "🚫 Política de Riesgo: Este cliente tiene solo {$resultado['total']} transacción(es). Las primeras 5 órdenes son obligatoriamente de CONTADO.";
            http_response_code(403);
        } else {
            http_response_code(200);
        }

        echo json_encode($response);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // REGLA 3: Crear pedido con bloqueo pesimista de inventario
    // ─────────────────────────────────────────────────────────────────
    if ($peticion === 'crear') {
        $cedula    = trim($_POST['cedula_cliente'] ?? '');
        if (empty($cedula)) {
            $cedula = $_SESSION['usuario']['cedula'] ?? '';
        }
        $itemsJson = $_POST['items'] ?? '[]';
        $itemsRaw  = json_decode($itemsJson, true) ?: [];

        // Normalizar claves: el carrito puede enviar {id, precio} o {id_producto, precio_unitario}
        $items = array_map(function($item) {
            return [
                'id_producto'    => $item['id_producto'] ?? $item['id'] ?? null,
                'cantidad'       => (int) ($item['cantidad'] ?? 1),
                'precio_unitario'=> (float) ($item['precio_unitario'] ?? $item['precio'] ?? 0),
            ];
        }, $itemsRaw);

        // Filtrar items inválidos (sin id_producto)
        $items = array_filter($items, fn($i) => !empty($i['id_producto']));
        $items = array_values($items);

        if (empty($cedula) || empty($items)) {
            http_response_code(400);
            echo json_encode(['resultado' => 400, 'icon' => 'error', 'mensaje' => 'Datos del pedido incompletos.']);
            exit;
        }

        // ── Pre-validación Regla 1 ────────────────────────────────────
        $clienteModel = new Cliente();
        $morosidad    = $clienteModel->Transaccion(['peticion' => 'verificar_morosidad', 'cedula' => $cedula]);
        if ($morosidad['bloqueado'] ?? false) {
            http_response_code(403);
            echo json_encode($morosidad['response']);
            exit;
        }

        // ── Pre-validación Regla 2 ────────────────────────────────────
        $condicionPago = strtoupper($_POST['condicion_pago'] ?? 'CONTADO');
        if ($condicionPago === 'CREDITO') {
            $historial = $clienteModel->Transaccion(['peticion' => 'conteo_transacciones', 'cedula' => $cedula]);
            if ($historial['en_probacion'] ?? false) {
                http_response_code(403);
                echo json_encode($historial['response'] + ['bloqueado_por_nuevo' => true]);
                exit;
            }
        }

        // ── Crear pedido (Regla 3 — FOR UPDATE interno en el modelo) ─
        $pedidoModel = new Pedido();
        $pedidoModel->setCedulaCliente($cedula);
        $pedidoModel->setCedulaVendedor($_SESSION['usuario']['cedula'] ?? null);
        $pedidoModel->setCondicionPago($condicionPago);
        $pedidoModel->setMetodoPago(strtoupper($_POST['metodo_pago'] ?? 'EFECTIVO'));
        $pedidoModel->setDescuento((float) ($_POST['descuento'] ?? 0));
        $pedidoModel->setObservacion($_POST['observacion'] ?? null);
        $pedidoModel->setFechaEntrega($_POST['fecha_entrega'] ?? null);
        $pedidoModel->setItems($items);

        $resultado = $pedidoModel->Transaccion(['peticion' => 'crear']);

        http_response_code($resultado['HTTP_STATUS']['codigo']);
        echo json_encode($resultado['response'] + [
            'id_pedido'     => $resultado['id_pedido']     ?? null,
            'numero_pedido' => $resultado['numero_pedido'] ?? null,
        ]);
        exit;
    }

    // ── Listar pedidos ────────────────────────────────────────────────
    if ($peticion === 'listar') {
        $pedidoModel = new Pedido();
        $resultado   = $pedidoModel->Transaccion(['peticion' => 'listar']);
        echo json_encode($resultado['response']);
        exit;
    }

    // ── Buscar pedido ─────────────────────────────────────────────────
    if ($peticion === 'buscar') {
        $pedidoModel = new Pedido();
        $resultado   = $pedidoModel->Transaccion([
            'peticion'   => 'buscar',
            'id_pedido'  => trim($_POST['id_pedido'] ?? ''),
        ]);
        http_response_code($resultado['HTTP_STATUS']['codigo']);
        echo json_encode($resultado['response']);
        exit;
    }

    // ── Cambiar estado ────────────────────────────────────────────────
    if ($peticion === 'cambiar_estado') {
        $pedidoModel = new Pedido();
        $resultado   = $pedidoModel->Transaccion([
            'peticion'   => 'cambiar_estado',
            'id_pedido'  => trim($_POST['id_pedido'] ?? ''),
            'estado'     => strtoupper(trim($_POST['estado'] ?? '')),
        ]);
        http_response_code($resultado['HTTP_STATUS']['codigo']);
        echo json_encode($resultado['response']);
        exit;
    }

    // ── Cancelar pedido ───────────────────────────────────────────────
    if ($peticion === 'cancelar') {
        $pedidoModel = new Pedido();
        $resultado   = $pedidoModel->Transaccion([
            'peticion'  => 'cancelar',
            'id_pedido' => trim($_POST['id_pedido'] ?? ''),
        ]);
        http_response_code($resultado['HTTP_STATUS']['codigo']);
        echo json_encode($resultado['response']);
        exit;
    }

    // ── Listar pedidos ────────────────────────────────────────────────
    if ($peticion === 'listar') {
        $pedidoModel = new Pedido();
        $resultado   = $pedidoModel->Transaccion(['peticion' => 'listar']);
        http_response_code($resultado['HTTP_STATUS']['codigo']);
        echo json_encode($resultado['response']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['resultado' => 400, 'mensaje' => 'Petición no reconocida.']);
    exit;
}
