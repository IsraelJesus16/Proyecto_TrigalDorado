<?php

define('BASE_PATH', realpath(__DIR__ . '/..'));
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    $db = new Database();
    $pdo = $db->LlamarConexion();
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Limpiar tablas para evitar duplicados si se corre varias veces (opcional, pero mejor solo insertar si no existen)
    // Para simplificar, insertaremos ignorando errores o borrando primero.
    $pdo->exec("DELETE FROM movimiento_inventario WHERE id_entidad IN (SELECT id_producto FROM producto WHERE nombre LIKE '%Premium%')");
    $pdo->exec("DELETE FROM inventario_producto WHERE id_producto IN (SELECT id_producto FROM producto WHERE nombre LIKE '%Premium%')");
    $pdo->exec("DELETE FROM producto WHERE nombre LIKE '%Premium%' OR nombre LIKE '%Artesanal%' OR nombre LIKE '%Gallego%'");

    // Insertar categorías primero
    $sqlCat = "INSERT IGNORE INTO categoria_producto (id_categoria, nombre, estatus) VALUES 
               ('CAT-GALL', 'Galletas Tradicionales', 1),
               ('CAT-PAN', 'Panes de Larga Duración', 1),
               ('CAT-PONQ', 'Ponqués Artesanales', 1)";
    $pdo->exec($sqlCat);

    $productos = [
        [
            'id' => 'PRD-G001',
            'cat' => 'CAT-GALL',
            'nombre' => 'Galletas Rizadas Premium',
            'desc' => 'Nuestra tradicional galleta rizada, ahora con doble horneado para un crujido inigualable. Ideal para acompañar con café.',
            'precio' => 45.50,
            'peso' => 250,
            'unidad' => 'Paquete',
            'destacado' => 1,
            'imagen' => 'placeholder.jpg'
        ],
        [
            'id' => 'PRD-G002',
            'cat' => 'CAT-GALL',
            'nombre' => 'Galletas de Mantequilla',
            'desc' => 'Suaves por dentro, crujientes por fuera. Receta original de la familia Mendoza con mantequilla 100% natural.',
            'precio' => 55.00,
            'peso' => 300,
            'unidad' => 'Paquete',
            'destacado' => 0,
            'imagen' => 'placeholder.jpg'
        ],
        [
            'id' => 'PRD-P001',
            'cat' => 'CAT-PONQ',
            'nombre' => 'Ponqué Marmoleado Artesanal',
            'desc' => 'Esponjoso ponqué de vainilla y chocolate. Perfecto para meriendas familiares y celebraciones.',
            'precio' => 120.00,
            'peso' => 500,
            'unidad' => 'Unidad',
            'destacado' => 1,
            'imagen' => 'placeholder.jpg'
        ],
        [
            'id' => 'PRD-P002',
            'cat' => 'CAT-PONQ',
            'nombre' => 'Ponqué de Naranja y Semillas',
            'desc' => 'Ponqué cítrico con ralladura de naranja natural y semillas de amapola. Refrescante y nutritivo.',
            'precio' => 135.00,
            'peso' => 500,
            'unidad' => 'Unidad',
            'destacado' => 0,
            'imagen' => 'placeholder.jpg'
        ],
        [
            'id' => 'PRD-N001',
            'cat' => 'CAT-PAN',
            'nombre' => 'Pan Gallego de Larga Duración',
            'desc' => 'Pan rústico de corteza gruesa y miga densa. Excelente para sándwiches o acompañar comidas.',
            'precio' => 35.00,
            'peso' => 400,
            'unidad' => 'Unidad',
            'destacado' => 1,
            'imagen' => 'placeholder.jpg'
        ],
        [
            'id' => 'PRD-N002',
            'cat' => 'CAT-PAN',
            'nombre' => 'Campesino Bis Cotus',
            'desc' => 'Pan campesino con la técnica de doble horneado que prolonga su vida útil hasta por 15 días.',
            'precio' => 30.00,
            'peso' => 350,
            'unidad' => 'Unidad',
            'destacado' => 0,
            'imagen' => 'placeholder.jpg'
        ]
    ];

    $sqlProd = "INSERT INTO producto (id_producto, id_categoria, nombre, descripcion, precio_venta, peso_neto, unidad_venta, estatus, destacado, imagen_url)
                VALUES (:id, :cat, :nom, :desc, :pre, :pes, :uni, 1, :dest, :img)";
    $stmtProd = $pdo->prepare($sqlProd);

    $sqlInv = "INSERT INTO inventario_producto (id_producto, cantidad_actual, cantidad_minima) VALUES (:id, 100, 20)";
    $stmtInv = $pdo->prepare($sqlInv);

    foreach ($productos as $p) {
        $stmtProd->execute([
            ':id' => $p['id'],
            ':cat' => $p['cat'],
            ':nom' => $p['nombre'],
            ':desc' => $p['desc'],
            ':pre' => $p['precio'],
            ':pes' => $p['peso'],
            ':uni' => $p['unidad'],
            ':dest' => $p['destacado'],
            ':img' => $p['imagen']
        ]);

        $stmtInv->execute([':id' => $p['id']]);
        
        echo "Insertado: {$p['nombre']} (Stock: 100)\n";
    }

    $pdo->commit();
    echo "¡Seeding completado con éxito!\n";

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($db)) $db->DestruirConexion();
}
