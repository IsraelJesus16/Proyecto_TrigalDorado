<?php
namespace App\Controllers;

use App\Models\Producto;
use App\Helpers\Helper;

// API endpoints
$action = $_REQUEST['action'] ?? null;
$type   = $_REQUEST['type'] ?? 'index';

if ($action) {
    header('Content-Type: application/json');
    $producto = new Producto();

    if ($action === 'consultar') {
        echo json_encode($producto->Transaccion(['peticion' => 'consultar', 'solo_activos' => false]));
        return;
    }

    if ($action === 'buscar' && isset($_POST['id_producto'])) {
        echo json_encode($producto->Transaccion(['peticion' => 'buscar', 'id_producto' => $_POST['id_producto']]));
        return;
    }

    if ($action === 'listar_ingredientes') {
        echo json_encode($producto->Transaccion(['peticion' => 'listar_ingredientes']));
        return;
    }

    if ($action === 'guardar') {
        $producto->setIdCategoria($_POST['id_categoria'] ?? '');
        $producto->setNombre($_POST['nombre'] ?? '');
        $producto->setDescripcion($_POST['descripcion'] ?? '');
        $producto->setPrecioVenta((float)($_POST['precio_venta'] ?? 0));
        $producto->setPesoNeto((float)($_POST['peso_neto'] ?? 0));
        $producto->setUnidadVenta($_POST['unidad_venta'] ?? 'Paquete');
        $producto->setDestacado(isset($_POST['destacado']) ? 1 : 0);
        
        // Manejo básico de imagen (dummy URL o lógica real si hay upload)
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/public/assets/img/productos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = uniqid() . '-' . basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadDir . $fileName);
            $producto->setImagenUrl($fileName);
        } else {
            $producto->setImagenUrl($_POST['imagen_actual'] ?? null);
        }

        if (isset($_POST['receta'])) {
            $recetaArr = json_decode($_POST['receta'], true);
            if (is_array($recetaArr)) {
                $producto->setReceta($recetaArr);
            }
        }

        if (!empty($_POST['id_producto'])) {
            $producto->setIdProducto($_POST['id_producto']);
            echo json_encode($producto->Transaccion(['peticion' => 'modificar']));
        } else {
            echo json_encode($producto->Transaccion(['peticion' => 'registrar']));
        }
        return;
    }

    if ($action === 'eliminar' && isset($_POST['id_producto'])) {
        $producto->setIdProducto($_POST['id_producto']);
        echo json_encode($producto->Transaccion(['peticion' => 'eliminar']));
        return;
    }

    echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'Acción no válida']]);
    return;
}

// Vista
if ($type === 'index') {
    $titulo = 'Catálogo de Productos — El Trigal Dorado';
    
    // Obtener categorías para el select (usaremos la conexión directa para esto o un Modelo CategoriaProducto)
    $db = new \App\Core\Database();
    $db->LlamarConexion();
    $stmt = $db->LlamarConexion()->query("SELECT id_categoria, nombre FROM categoria_producto WHERE estatus = 1 ORDER BY orden");
    $categorias = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $db->DestruirConexion();

    require_once BASE_PATH . '/resources/views/admin/productos/index.php';
    return;
}
