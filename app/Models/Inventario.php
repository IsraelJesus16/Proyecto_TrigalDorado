<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Helper;
use PDO;

class Inventario extends Database
{
    public function Transaccion(array $peticion): array
    {
        return match ($peticion['peticion'] ?? '') {
            'consultar'    => $this->consultar(),
            'ajustar'      => $this->ajustar(
                $peticion['id_producto'],
                (float)$peticion['cantidad'],
                $peticion['tipo'],
                $peticion['motivo'],
                $peticion['cedula_usuario'] ?? 'Sistema'
            ),
            'consultar_mp' => $this->consultarMP(),
            'ajustar_mp'   => $this->ajustarMP(
                $peticion['id_materia'],
                (float)$peticion['cantidad'],
                $peticion['tipo'],
                $peticion['motivo'],
                $peticion['cedula_usuario'] ?? 'Sistema'
            ),
            default => [
                'estado'      => -1,
                'response'    => ['resultado' => 400, 'mensaje' => 'Petición no válida'],
                'HTTP_STATUS' => ['codigo' => 400],
            ],
        };
    }

    // ─── PRODUCTOS TERMINADOS ────────────────────────────────────────

    private function consultar(): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT i.*, p.nombre, p.imagen_url, c.nombre AS categoria
                    FROM inventario_producto i
                    INNER JOIN producto p ON i.id_producto = p.id_producto
                    INNER JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                    WHERE p.estatus = 1
                    ORDER BY p.nombre";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute();

            return [
                'estado'      => 1,
                'response'    => ['datos' => $stm->fetchAll(PDO::FETCH_ASSOC)],
                'HTTP_STATUS' => ['codigo' => 200]
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage());
            return ['estado' => -1, 'response' => ['mensaje' => 'Error interno al consultar inventario']];
        } finally {
            $this->DestruirConexion();
        }
    }

    private function ajustar(string $idProducto, float $cantidad, string $tipo, string $motivo, string $usuario): array
    {
        try {
            $this->LlamarConexion();
            $this->LlamarConexion()->beginTransaction();

            if ($tipo === 'AJUSTE') {
                $sqlInv = "UPDATE inventario_producto
                           SET cantidad_actual = :cant
                           WHERE id_producto = :id";
                $params = [':cant' => $cantidad, ':id' => $idProducto];
            } else if ($tipo === 'ENTRADA') {
                $sqlInv = "UPDATE inventario_producto
                           SET cantidad_actual = cantidad_actual + :cant,
                               ultima_entrada = NOW()
                           WHERE id_producto = :id";
                $params = [':cant' => $cantidad, ':id' => $idProducto];
            } else { // SALIDA
                $sqlInv = "UPDATE inventario_producto
                           SET cantidad_actual = cantidad_actual - :cant
                           WHERE id_producto = :id AND cantidad_actual >= :cant_check";
                $params = [':cant' => $cantidad, ':cant_check' => $cantidad, ':id' => $idProducto];
            }

            $stmInv = $this->LlamarConexion()->prepare($sqlInv);
            $stmInv->execute($params);

            if ($stmInv->rowCount() === 0 && $tipo === 'SALIDA') {
                $this->LlamarConexion()->rollBack();
                return ['estado' => -1, 'response' => ['mensaje' => 'Stock insuficiente para registrar la salida']];
            }

            // Bitácora
            $sqlMov = "INSERT INTO movimiento_inventario (tipo, entidad, id_entidad, cantidad, motivo, cedula_usuario)
                       VALUES (:tipo, 'PRODUCTO', :id, :cant, :motivo, :user)";
            $this->LlamarConexion()->prepare($sqlMov)->execute([
                ':tipo'   => $tipo,
                ':id'     => $idProducto,
                ':cant'   => $cantidad,
                ':motivo' => $motivo,
                ':user'   => $usuario
            ]);

            $this->LlamarConexion()->commit();
            return ['estado' => 1, 'response' => ['mensaje' => 'Inventario de producto actualizado']];

        } catch (\PDOException $e) {
            if ($this->LlamarConexion()->inTransaction()) $this->LlamarConexion()->rollBack();
            Helper::ErrorLog($e->getMessage());
            return ['estado' => -1, 'response' => ['mensaje' => 'Error interno al ajustar inventario']];
        } finally {
            $this->DestruirConexion();
        }
    }

    // ─── MATERIA PRIMA / INSUMOS ─────────────────────────────────────

    private function consultarMP(): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT i.*, m.nombre, m.unidad_medida, m.descripcion
                    FROM inventario_materia_prima i
                    INNER JOIN materia_prima m ON i.id_materia = m.id_materia
                    WHERE m.estatus = 1
                    ORDER BY m.nombre";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute();

            return [
                'estado'      => 1,
                'response'    => ['datos' => $stm->fetchAll(PDO::FETCH_ASSOC)],
                'HTTP_STATUS' => ['codigo' => 200]
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage());
            return ['estado' => -1, 'response' => ['mensaje' => 'Error interno al consultar materia prima']];
        } finally {
            $this->DestruirConexion();
        }
    }

    private function ajustarMP(string $idMateria, float $cantidad, string $tipo, string $motivo, string $usuario): array
    {
        try {
            $this->LlamarConexion();
            $this->LlamarConexion()->beginTransaction();

            if ($tipo === 'AJUSTE') {
                $sqlInv = "UPDATE inventario_materia_prima
                           SET cantidad_actual = :cant
                           WHERE id_materia = :id";
                $params = [':cant' => $cantidad, ':id' => $idMateria];
            } else if ($tipo === 'ENTRADA') {
                $sqlInv = "UPDATE inventario_materia_prima
                           SET cantidad_actual = cantidad_actual + :cant,
                               ultima_entrada = NOW()
                           WHERE id_materia = :id";
                $params = [':cant' => $cantidad, ':id' => $idMateria];
            } else { // SALIDA
                $sqlInv = "UPDATE inventario_materia_prima
                           SET cantidad_actual = cantidad_actual - :cant
                           WHERE id_materia = :id AND cantidad_actual >= :cant_check";
                $params = [':cant' => $cantidad, ':cant_check' => $cantidad, ':id' => $idMateria];
            }

            $stmInv = $this->LlamarConexion()->prepare($sqlInv);
            $stmInv->execute($params);

            if ($stmInv->rowCount() === 0 && $tipo === 'SALIDA') {
                $this->LlamarConexion()->rollBack();
                return ['estado' => -1, 'response' => ['mensaje' => 'Stock insuficiente de este insumo para registrar la salida']];
            }

            // Si el insumo no tenía registro de stock, insertar uno
            if ($stmInv->rowCount() === 0 && $tipo !== 'SALIDA') {
                $sqlIns = "INSERT INTO inventario_materia_prima (id_materia, cantidad_actual, cantidad_minima, ultima_entrada)
                           VALUES (:id, :cant, 0, NOW())
                           ON DUPLICATE KEY UPDATE
                               cantidad_actual = cantidad_actual + :cant,
                               ultima_entrada  = NOW()";
                $this->LlamarConexion()->prepare($sqlIns)->execute([':id' => $idMateria, ':cant' => $cantidad]);
            }

            // Bitácora
            $sqlMov = "INSERT INTO movimiento_inventario (tipo, entidad, id_entidad, cantidad, motivo, cedula_usuario)
                       VALUES (:tipo, 'MATERIA_PRIMA', :id, :cant, :motivo, :user)";
            $this->LlamarConexion()->prepare($sqlMov)->execute([
                ':tipo'   => $tipo,
                ':id'     => $idMateria,
                ':cant'   => $cantidad,
                ':motivo' => $motivo,
                ':user'   => $usuario
            ]);

            $this->LlamarConexion()->commit();
            return ['estado' => 1, 'response' => ['mensaje' => 'Stock de insumo actualizado correctamente']];

        } catch (\PDOException $e) {
            if ($this->LlamarConexion()->inTransaction()) $this->LlamarConexion()->rollBack();
            Helper::ErrorLog($e->getMessage());
            return ['estado' => -1, 'response' => ['mensaje' => 'Error interno al ajustar materia prima']];
        } finally {
            $this->DestruirConexion();
        }
    }
}
