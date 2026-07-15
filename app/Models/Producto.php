<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Helper;
use PDO;

/**
 * Producto — Modelo OOP para el catálogo de productos.
 * Soporta gestión de imágenes (upload + URL generada).
 */
class Producto extends Database
{
    private ?string $idProducto   = null;
    private ?string $idCategoria  = null;
    private ?string $nombre       = null;
    private ?string $descripcion  = null;
    private ?float  $precioVenta  = null;
    private ?float  $pesoNeto     = null;
    private string  $unidadVenta  = 'Paquete';
    private ?string $imagenUrl    = null;
    private int     $destacado    = 0;
    private int     $estatus      = 1;
    private array   $receta       = [];
    private bool    $hasReceta    = false;

    // Setters
    public function setIdProducto(?string $v): void  { $this->idProducto  = $v; }
    public function setIdCategoria(string $v): void  { $this->idCategoria = trim($v); }
    public function setNombre(string $v): void        { $this->nombre      = trim($v); }
    public function setDescripcion(?string $v): void  { $this->descripcion = $v; }
    public function setPrecioVenta(float $v): void    { $this->precioVenta = $v; }
    public function setPesoNeto(?float $v): void      { $this->pesoNeto    = $v; }
    public function setUnidadVenta(string $v): void   { $this->unidadVenta = $v; }
    public function setImagenUrl(?string $v): void    { $this->imagenUrl   = $v; }
    public function setDestacado(int $v): void        { $this->destacado   = $v ? 1 : 0; }
    public function setEstatus(int $v): void          { $this->estatus     = $v ? 1 : 0; }
    public function setReceta(array $v): void         { $this->receta = $v; $this->hasReceta = true; }

    public function Transaccion(array $peticion): array
    {
        return match ($peticion['peticion'] ?? '') {
            'registrar'  => $this->registrar(),
            'consultar'  => $this->consultar($peticion['solo_activos'] ?? true),
            'buscar'     => $this->buscar($peticion['id_producto'] ?? ''),
            'modificar'  => $this->modificar(),
            'eliminar'   => $this->eliminar(),
            'destacados' => $this->listarDestacados(),
            'por_categoria' => $this->porCategoria($peticion['id_categoria'] ?? ''),
            'listar_ingredientes' => $this->listarIngredientes(),
            default      => [
                'estado'      => -1,
                'response'    => ['resultado' => 400, 'icon' => 'error', 'mensaje' => 'Petición no válida.'],
                'HTTP_STATUS' => ['codigo' => 400],
            ],
        };
    }

    private function registrar(): array
    {
        try {
            $this->LlamarConexion();
            $this->LlamarConexion()->beginTransaction();

            $id = Helper::uuid();
            $this->LlamarConexion()->prepare(
                "INSERT INTO producto
                 (id_producto, id_categoria, nombre, descripcion, precio_venta, peso_neto, unidad_venta, imagen_url, destacado)
                 VALUES (:id, :cat, :n, :d, :pv, :pn, :uv, :img, :dest)"
            )->execute([
                ':id'   => $id, ':cat'  => $this->idCategoria, ':n'   => $this->nombre,
                ':d'    => $this->descripcion, ':pv'  => $this->precioVenta,
                ':pn'   => $this->pesoNeto,    ':uv'  => $this->unidadVenta,
                ':img'  => $this->imagenUrl,   ':dest'=> $this->destacado,
            ]);

            // Crear registro de inventario en cero
            $this->LlamarConexion()->prepare(
                "INSERT INTO inventario_producto (id_producto, cantidad_actual, cantidad_minima)
                 VALUES (:id, 0, 10)"
            )->execute([':id' => $id]);

            if ($this->hasReceta && !empty($this->receta)) {
                $stmReceta = $this->LlamarConexion()->prepare("INSERT INTO receta (id_producto, id_materia, cantidad) VALUES (?, ?, ?)");
                foreach ($this->receta as $item) {
                    $stmReceta->execute([$id, $item['id_materia'], $item['cantidad']]);
                }
            }

            $this->LlamarConexion()->commit();

            return [
                'estado'      => 1,
                'id_producto' => $id,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Producto registrado exitosamente.'],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            if ($this->LlamarConexion()->inTransaction()) $this->LlamarConexion()->rollBack();
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function consultar(bool $soloActivos = true): array
    {
        try {
            $this->LlamarConexion();
            $where = $soloActivos ? "WHERE p.estatus = 1" : "";
            $sql   = "SELECT p.*, cat.nombre AS nombre_categoria, cat.color_badge,
                             COALESCE(inv.cantidad_actual, 0) AS stock,
                             COALESCE(inv.cantidad_minima, 0) AS stock_minimo
                      FROM producto p
                      INNER JOIN categoria_producto cat ON p.id_categoria = cat.id_categoria
                      LEFT JOIN inventario_producto inv ON p.id_producto = inv.id_producto
                      {$where}
                      ORDER BY cat.orden, p.nombre";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute();
            $datos = $stm->fetchAll(PDO::FETCH_ASSOC);

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'datos' => $datos],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function buscar(string $idProducto): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT p.*, cat.nombre AS nombre_categoria, cat.color_badge,
                           COALESCE(inv.cantidad_actual, 0) AS stock
                    FROM producto p
                    INNER JOIN categoria_producto cat ON p.id_categoria = cat.id_categoria
                    LEFT JOIN inventario_producto inv ON p.id_producto = inv.id_producto
                    WHERE p.id_producto = :id LIMIT 1";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute([':id' => $idProducto]);
            $dato = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$dato) {
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 404, 'icon' => 'error', 'mensaje' => 'Producto no encontrado.'],
                    'HTTP_STATUS' => ['codigo' => 404],
                ];
            }

            // Consultar ventas del mes actual
            $sqlVentas = "SELECT COALESCE(SUM(d.cantidad), 0) AS total_vendido
                          FROM detalle_pedido d
                          INNER JOIN pedido p ON d.id_pedido = p.id_pedido
                          WHERE d.id_producto = :id
                            AND p.estado != 'CANCELADO'
                            AND MONTH(p.fecha_pedido) = MONTH(CURRENT_DATE())
                            AND YEAR(p.fecha_pedido) = YEAR(CURRENT_DATE())";
            $stmVentas = $this->LlamarConexion()->prepare($sqlVentas);
            $stmVentas->execute([':id' => $idProducto]);
            $dato['ventas_mes'] = (int) $stmVentas->fetchColumn();

            // Consultar receta / ingredientes
            $sqlIngredientes = "SELECT r.id_materia, m.nombre, r.cantidad, m.unidad_medida
                                FROM receta r
                                INNER JOIN materia_prima m ON r.id_materia = m.id_materia
                                WHERE r.id_producto = :id";
            $stmIng = $this->LlamarConexion()->prepare($sqlIngredientes);
            $stmIng->execute([':id' => $idProducto]);
            $dato['ingredientes'] = $stmIng->fetchAll(PDO::FETCH_ASSOC);
            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'datos' => $dato],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function modificar(): array
    {
        try {
            $this->LlamarConexion();
            $this->LlamarConexion()->beginTransaction();

            $this->LlamarConexion()->prepare(
                "UPDATE producto SET id_categoria = :cat, nombre = :n, descripcion = :d,
                 precio_venta = :pv, peso_neto = :pn, unidad_venta = :uv,
                 imagen_url = :img, destacado = :dest
                 WHERE id_producto = :id"
            )->execute([
                ':cat'  => $this->idCategoria, ':n'   => $this->nombre,
                ':d'    => $this->descripcion, ':pv'  => $this->precioVenta,
                ':pn'   => $this->pesoNeto,    ':uv'  => $this->unidadVenta,
                ':img'  => $this->imagenUrl,   ':dest'=> $this->destacado,
                ':id'   => $this->idProducto,
            ]);

            if ($this->hasReceta) {
                $this->LlamarConexion()->prepare("DELETE FROM receta WHERE id_producto = ?")->execute([$this->idProducto]);
                if (!empty($this->receta)) {
                    $stmReceta = $this->LlamarConexion()->prepare("INSERT INTO receta (id_producto, id_materia, cantidad) VALUES (?, ?, ?)");
                    foreach ($this->receta as $item) {
                        $stmReceta->execute([$this->idProducto, $item['id_materia'], $item['cantidad']]);
                    }
                }
            }

            $this->LlamarConexion()->commit();

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Producto actualizado exitosamente.'],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            if ($this->LlamarConexion()->inTransaction()) $this->LlamarConexion()->rollBack();
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function eliminar(): array
    {
        try {
            $this->LlamarConexion();
            $this->LlamarConexion()->prepare("UPDATE producto SET estatus = 0 WHERE id_producto = :id")
                ->execute([':id' => $this->idProducto]);
            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Producto deshabilitado.'],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function listarDestacados(): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT p.*, cat.nombre AS nombre_categoria, cat.color_badge
                    FROM producto p
                    INNER JOIN categoria_producto cat ON p.id_categoria = cat.id_categoria
                    WHERE p.destacado = 1 AND p.estatus = 1
                    ORDER BY cat.orden, p.nombre
                    LIMIT 12";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute();
            return ['estado' => 1, 'response' => ['resultado' => 200, 'datos' => $stm->fetchAll(PDO::FETCH_ASSOC)]];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage());
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function porCategoria(string $idCategoria): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT p.*, COALESCE(inv.cantidad_actual, 0) AS stock
                    FROM producto p
                    LEFT JOIN inventario_producto inv ON p.id_producto = inv.id_producto
                    WHERE p.id_categoria = :cat AND p.estatus = 1
                    ORDER BY p.nombre";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute([':cat' => $idCategoria]);
            return ['estado' => 1, 'response' => ['resultado' => 200, 'datos' => $stm->fetchAll(PDO::FETCH_ASSOC)]];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage());
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function listarIngredientes(): array
    {
        try {
            $this->LlamarConexion();
            $stm = $this->LlamarConexion()->query("SELECT id_materia, nombre, unidad_medida FROM materia_prima WHERE estatus = 1 ORDER BY nombre");
            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'datos' => $stm->fetchAll(PDO::FETCH_ASSOC)],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
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
            'response'    => ['resultado' => 500, 'icon' => 'error', 'mensaje' => 'Error interno en el servidor.'],
            'HTTP_STATUS' => ['codigo' => 500],
        ];
    }
}
