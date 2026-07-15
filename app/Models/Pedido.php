<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Helper;
use PDO;
use Exception;

/**
 * Pedido — Modelo OOP para la gestión de órdenes de venta.
 *
 * Implementa la Regla de Negocio 3:
 *   Bloqueo pesimista (SELECT ... FOR UPDATE) para garantizar
 *   integridad ACID en operaciones concurrentes de inventario.
 *
 * Flujo de creación de pedido:
 *   1. Verificar morosidad del cliente (Regla 1 via ClienteModel)
 *   2. Verificar historial de transacciones (Regla 2 via ClienteModel)
 *   3. START TRANSACTION
 *   4. SELECT stock FOR UPDATE (bloqueo pesimista por fila)
 *   5. Validar disponibilidad
 *   6. Descontar inventario
 *   7. Insertar pedido + detalles
 *   8. COMMIT (o ROLLBACK si falla)
 */
class Pedido extends Database
{
    private ?string $cedula_cliente  = null;
    private ?string $cedula_vendedor = null;
    private string  $condicion_pago  = 'CONTADO';
    private string  $metodo_pago     = 'EFECTIVO';
    private string  $estado          = 'PENDIENTE';
    private float   $descuento       = 0.0;
    private ?string $observacion     = null;
    private ?string $fecha_entrega   = null;
    private array   $items           = [];

    // ── Setters ────────────────────────────────────────────────────────

    public function setCedulaCliente(string $v): void   { $this->cedula_cliente  = trim($v); }
    public function setCedulaVendedor(?string $v): void { $this->cedula_vendedor = $v; }
    public function setCondicionPago(string $v): void   { $this->condicion_pago  = $v; }
    public function setMetodoPago(string $v): void      { $this->metodo_pago     = $v; }
    public function setDescuento(float $v): void        { $this->descuento       = $v; }
    public function setObservacion(?string $v): void    { $this->observacion     = $v; }
    public function setFechaEntrega(?string $v): void   { $this->fecha_entrega   = $v; }

    /**
     * @param array $items  [ ['id_producto' => '...', 'cantidad' => 2, 'precio_unitario' => 10.50], ... ]
     */
    public function setItems(array $items): void        { $this->items = $items; }

    // ── Dispatcher ─────────────────────────────────────────────────────

    public function Transaccion(array $peticion): array
    {
        return match ($peticion['peticion'] ?? '') {
            'crear'          => $this->crearPedido(),
            'listar'         => $this->listar(),
            'buscar'         => $this->buscar($peticion['id_pedido'] ?? ''),
            'cambiar_estado' => $this->cambiarEstado($peticion['id_pedido'] ?? '', $peticion['estado'] ?? ''),
            'cancelar'       => $this->cancelar($peticion['id_pedido'] ?? ''),
            default          => [
                'estado'      => -1,
                'response'    => ['resultado' => 400, 'icon' => 'error', 'mensaje' => 'Petición no válida.'],
                'HTTP_STATUS' => ['codigo' => 400],
            ],
        };
    }

    // ════════════════════════════════════════════════════════════════════
    // REGLA DE NEGOCIO 3 — Inventario con bloqueo pesimista ACID
    // ════════════════════════════════════════════════════════════════════

    /**
     * Crea un pedido verificando el inventario con SELECT ... FOR UPDATE.
     *
     * El bloqueo pesimista garantiza que si dos vendedores intentan
     * vender las mismas unidades simultáneamente, solo uno lo logra.
     * El segundo recibe un mensaje de stock insuficiente.
     *
     * @return array
     */
    private function crearPedido(): array
    {
        if (empty($this->items)) {
            return [
                'estado'      => -1,
                'response'    => ['resultado' => 400, 'icon' => 'error', 'mensaje' => 'El pedido no contiene productos.'],
                'HTTP_STATUS' => ['codigo' => 400],
            ];
        }

        try {
            $pdo = $this->LlamarConexion();

            // ── INICIO DE TRANSACCIÓN ACID ──────────────────────────
            $pdo->beginTransaction();

            $subtotal = 0.0;
            $idPedido = Helper::uuid();

            // ── VERIFICACIÓN Y DESCUENTO DE INVENTARIO (FOR UPDATE) ─
            foreach ($this->items as $item) {
                $idProd   = $item['id_producto'];
                $cantidad = (int) $item['cantidad'];

                // Regla 3: Bloqueo pesimista — la fila queda bloqueada
                // hasta que el COMMIT libere el lock
                $sqlStock = "SELECT inv.cantidad_actual
                             FROM inventario_producto inv
                             WHERE inv.id_producto = :id
                             FOR UPDATE";

                $stmStock = $pdo->prepare($sqlStock);
                $stmStock->execute([':id' => $idProd]);
                $stockFila = $stmStock->fetch(PDO::FETCH_ASSOC);

                if (!$stockFila) {
                    $pdo->rollBack();
                    return [
                        'estado'      => -1,
                        'response'    => ['resultado' => 404, 'icon' => 'error',
                                          'mensaje'   => "Producto {$idProd} no tiene registro de inventario."],
                        'HTTP_STATUS' => ['codigo' => 404],
                    ];
                }

                $stockActual = (int) $stockFila['cantidad_actual'];

                if ($stockActual < $cantidad) {
                    $pdo->rollBack();
                    // Obtener nombre del producto para el mensaje
                    $stmNom = $pdo->prepare("SELECT nombre FROM producto WHERE id_producto = :id");
                    $stmNom->execute([':id' => $idProd]);
                    $nombre = $stmNom->fetchColumn() ?: $idProd;

                    return [
                        'estado'      => -1,
                        'response'    => [
                            'resultado' => 409,
                            'icon'      => 'warning',
                            'mensaje'   => "⚠️ Stock insuficiente: '{$nombre}' solo tiene {$stockActual} unidades disponibles. Solicitó {$cantidad}.",
                        ],
                        'HTTP_STATUS' => ['codigo' => 409],
                    ];
                }

                // Descontar el inventario dentro de la transacción
                $pdo->prepare(
                    "UPDATE inventario_producto
                     SET cantidad_actual = cantidad_actual - :qty,
                         fecha_update    = NOW()
                     WHERE id_producto   = :id"
                )->execute([':qty' => $cantidad, ':id' => $idProd]);

                // Registrar movimiento de salida
                $pdo->prepare(
                    "INSERT INTO movimiento_inventario
                     (tipo, entidad, id_entidad, cantidad, motivo, cedula_usuario)
                     VALUES ('SALIDA', 'PRODUCTO', :id, :qty, :mot, :usu)"
                )->execute([
                    ':id'  => $idProd,
                    ':qty' => $cantidad,
                    ':mot' => "Pedido #{$idPedido}",
                    ':usu' => $this->cedula_vendedor ?? $_SESSION['usuario']['cedula'] ?? null,
                ]);

                $subtotal += (float) $item['precio_unitario'] * $cantidad;
            }

            // ── CALCULAR TOTALES ─────────────────────────────────────
            $total = max(0.0, $subtotal - $this->descuento);

            // ── INSERTAR CABECERA DEL PEDIDO ─────────────────────────
            $pdo->prepare(
                "INSERT INTO pedido
                 (id_pedido, cedula_cliente, cedula_vendedor, condicion_pago,
                  metodo_pago, estado, subtotal, descuento, total, observacion, fecha_entrega)
                 VALUES (:id, :cli, :ven, :cp, :mp, 'PENDIENTE', :sub, :desc, :tot, :obs, :fe)"
            )->execute([
                ':id'   => $idPedido,
                ':cli'  => $this->cedula_cliente,
                ':ven'  => $this->cedula_vendedor,
                ':cp'   => $this->condicion_pago,
                ':mp'   => $this->metodo_pago,
                ':sub'  => $subtotal,
                ':desc' => $this->descuento,
                ':tot'  => $total,
                ':obs'  => $this->observacion,
                ':fe'   => $this->fecha_entrega,
            ]);

            // ── INSERTAR DETALLES ────────────────────────────────────
            $stmDet = $pdo->prepare(
                "INSERT INTO detalle_pedido
                 (id_detalle, id_pedido, id_producto, cantidad, precio_unitario)
                 VALUES (:det, :ped, :prod, :qty, :pu)"
            );

            foreach ($this->items as $item) {
                $stmDet->execute([
                    ':det'  => Helper::uuid(),
                    ':ped'  => $idPedido,
                    ':prod' => $item['id_producto'],
                    ':qty'  => (int)   $item['cantidad'],
                    ':pu'   => (float) $item['precio_unitario'],
                ]);
            }

            // ── Obtener número de pedido generado por AUTO_INCREMENT ─
            $stmNum = $pdo->prepare("SELECT numero_pedido FROM pedido WHERE id_pedido = :id");
            $stmNum->execute([':id' => $idPedido]);
            $numeroPedido = $stmNum->fetchColumn();

            // ── COMMIT: libera todos los bloqueos FOR UPDATE ─────────
            $pdo->commit();

            return [
                'estado'        => 1,
                'id_pedido'     => $idPedido,
                'numero_pedido' => $numeroPedido,
                'total'         => $total,
                'response'      => [
                    'resultado' => 200,
                    'icon'      => 'success',
                    'mensaje'   => "✅ Pedido #{$numeroPedido} registrado exitosamente. Total: Bs. " . number_format($total, 2),
                ],
                'HTTP_STATUS'   => ['codigo' => 200, 'mensaje' => 'OK'],
            ];

        } catch (\PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return [
                'estado'      => -1,
                'response'    => ['resultado' => 500, 'icon' => 'error', 'mensaje' => 'Error al registrar el pedido: ' . $e->getMessage()],
                'HTTP_STATUS' => ['codigo' => 500],
            ];
        } finally {
            $this->DestruirConexion();
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // CONSULTAS
    // ─────────────────────────────────────────────────────────────────

    private function listar(): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT p.id_pedido, p.numero_pedido, p.condicion_pago, p.metodo_pago,
                           p.estado, p.subtotal, p.descuento, p.total, p.observacion,
                           p.fecha_pedido, p.fecha_entrega,
                           per.nombre, per.apellido, per.telefono, per.cedula AS cedula_cliente
                    FROM pedido p
                    INNER JOIN persona per ON p.cedula_cliente = per.cedula
                    ORDER BY p.numero_pedido DESC";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute();
            $datos = $stm->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'data' => $datos],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function buscar(string $idPedido): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT p.*, per.nombre, per.apellido, per.telefono, per.correo
                    FROM pedido p
                    INNER JOIN persona per ON p.cedula_cliente = per.cedula
                    WHERE p.id_pedido = :id LIMIT 1";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute([':id' => $idPedido]);
            $pedido = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) {
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 404, 'icon' => 'error', 'mensaje' => 'Pedido no encontrado.'],
                    'HTTP_STATUS' => ['codigo' => 404],
                ];
            }

            // Cargar detalles
            $sqlDet = "SELECT dp.*, pr.nombre AS nombre_producto, pr.unidad_venta
                       FROM detalle_pedido dp
                       INNER JOIN producto pr ON dp.id_producto = pr.id_producto
                       WHERE dp.id_pedido = :id";
            $stmDet = $this->LlamarConexion()->prepare($sqlDet);
            $stmDet->execute([':id' => $idPedido]);
            $pedido['detalles'] = $stmDet->fetchAll(PDO::FETCH_ASSOC);

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'datos' => $pedido],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function cambiarEstado(string $idPedido, string $nuevoEstado): array
    {
        $estadosValidos = ['PENDIENTE', 'CONFIRMADO', 'PROCESANDO', 'DESPACHADO', 'ENTREGADO', 'CANCELADO'];

        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            return [
                'estado'      => -1,
                'response'    => ['resultado' => 400, 'icon' => 'error', 'mensaje' => 'Estado no válido.'],
                'HTTP_STATUS' => ['codigo' => 400],
            ];
        }

        try {
            $this->LlamarConexion();
            $this->LlamarConexion()->prepare(
                "UPDATE pedido SET estado = :e WHERE id_pedido = :id"
            )->execute([':e' => $nuevoEstado, ':id' => $idPedido]);

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => "Estado actualizado a: {$nuevoEstado}"],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function cancelar(string $idPedido): array
    {
        try {
            $pdo = $this->LlamarConexion();
            $pdo->beginTransaction();

            // Obtener detalles para restaurar el inventario
            $stmDet = $pdo->prepare(
                "SELECT id_producto, cantidad FROM detalle_pedido WHERE id_pedido = :id"
            );
            $stmDet->execute([':id' => $idPedido]);
            $detalles = $stmDet->fetchAll(PDO::FETCH_ASSOC);

            // Restaurar stock de cada producto
            foreach ($detalles as $det) {
                $pdo->prepare(
                    "UPDATE inventario_producto
                     SET cantidad_actual = cantidad_actual + :qty, fecha_update = NOW()
                     WHERE id_producto = :id"
                )->execute([':qty' => $det['cantidad'], ':id' => $det['id_producto']]);

                // Registrar movimiento de entrada (devolución)
                $pdo->prepare(
                    "INSERT INTO movimiento_inventario
                     (tipo, entidad, id_entidad, cantidad, motivo, cedula_usuario)
                     VALUES ('ENTRADA', 'PRODUCTO', :id, :qty, :mot, :usu)"
                )->execute([
                    ':id'  => $det['id_producto'],
                    ':qty' => $det['cantidad'],
                    ':mot' => "Cancelación pedido #{$idPedido}",
                    ':usu' => $_SESSION['usuario']['cedula'] ?? null,
                ]);
            }

            // Cambiar estado a CANCELADO
            $pdo->prepare("UPDATE pedido SET estado = 'CANCELADO' WHERE id_pedido = :id")
                ->execute([':id' => $idPedido]);

            $pdo->commit();

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Pedido cancelado y stock restaurado.'],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function respuestaError500(): array
    {
        return [
            'estado'      => -1,
            'response'    => ['resultado' => 500, 'icon' => 'error', 'mensaje' => 'Error interno. Intente de nuevo.'],
            'HTTP_STATUS' => ['codigo' => 500],
        ];
    }
}
