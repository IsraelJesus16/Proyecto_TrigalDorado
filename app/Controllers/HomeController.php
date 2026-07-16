<?php

namespace App\Controllers;

use App\Models\Producto;
use App\Helpers\Helper;

$type = $_REQUEST['type'] ?? 'index';

// ── Panel Público: Catálogo sin login ────────────────────────────────
if ($type === 'index' || $type === 'publico') {

    $productoModel = new Producto();

    // Cargar productos destacados para el Hero
    $destacados = $productoModel->Transaccion(['peticion' => 'destacados'])['response']['datos'] ?? [];

    // Cargar todos los productos activos agrupados por categoría
    $todosProductos = $productoModel->Transaccion(['peticion' => 'consultar', 'solo_activos' => true])['response']['datos'] ?? [];

    $pageRequested = strtolower($_REQUEST['page'] ?? 'home');
    $isHome = ($pageRequested === 'home' || $pageRequested === '');

    // Obtener todas las categorías para los filtros antes de truncar
    $categoriasFiltro = [];
    foreach ($todosProductos as $prod) {
        $categoriasFiltro[$prod['nombre_categoria']] = true;
    }
    $categoriasFiltro = array_keys($categoriasFiltro);

    // Agrupar por categoría
    $catalogo = [];
    $count = 0;
    foreach ($todosProductos as $prod) {
        if ($isHome && $count >= 8) break; // Mostrar max 8 productos (2 filas)
        $catalogo[$prod['nombre_categoria']][] = $prod;
        $count++;
    }

    $titulo          = 'El Trigal Dorado — Panadería Industrial Artesanal';
    $estaAutenticado = Helper::estaAutenticado();

    if ($pageRequested === 'catalogo') {
        $titulo = 'Catálogo Completo — El Trigal Dorado';
        require_once BASE_PATH . '/resources/views/public/catalogo.php';
    } else {
        require_once BASE_PATH . '/resources/views/public/home.php';
    }
}

// ── Endpoint AJAX: Verificar sesión para el carrito ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['peticion'])) {

    header('Content-Type: application/json');

    if ($_POST['peticion'] === 'verificar_sesion') {
        echo json_encode([
            'resultado'       => 200,
            'autenticado'     => Helper::estaAutenticado(),
            'usuario'         => $_SESSION['usuario'] ?? null,
            'csrf_token'      => $_SESSION['csrf_token'] ?? '',
        ]);
        exit;
    }

    if ($_POST['peticion'] === 'buscar_producto') {
        $id      = trim($_POST['id_producto'] ?? '');
        $modelo  = new Producto();
        $result  = $modelo->Transaccion(['peticion' => 'buscar', 'id_producto' => $id]);
        http_response_code($result['HTTP_STATUS']['codigo']);
        echo json_encode($result['response']);
        exit;
    }
}
