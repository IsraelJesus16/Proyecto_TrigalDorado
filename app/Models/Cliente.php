<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Helper;
use PDO;

/**
 * Cliente — Modelo OOP para la entidad Cliente.
 *
 * Encapsula la lógica de negocio del cliente incluyendo:
 *   - Regla 1: Bloqueo preventivo por morosidad > 7 días
 *   - Regla 2: Probación de nuevos clientes (primeras 5 transacciones = contado)
 */
class Cliente extends Database
{
    private ?string $cedula           = null;
    private ?string $nombre           = null;
    private ?string $apellido         = null;
    private ?string $fechaNac         = null;
    private ?string $sexo             = null;
    private ?string $telefono         = null;
    private ?string $correo           = null;
    private ?string $direccion        = null;
    private ?string $rif              = null;
    private ?string $razonSocial      = null;
    private string  $tipoCliente      = 'NATURAL';
    private float   $limiteCredito    = 0.0;
    private string  $condicionPago    = 'CONTADO';
    private int     $estatus          = 1;

    // ── Setters ────────────────────────────────────────────────────────

    public function setCedula(string $v): void       { $this->cedula        = trim($v); }
    public function setNombre(string $v): void       { $this->nombre        = trim($v); }
    public function setApellido(string $v): void     { $this->apellido      = trim($v); }
    public function setFechaNac(?string $v): void    { $this->fechaNac      = $v; }
    public function setSexo(?string $v): void        { $this->sexo          = $v; }
    public function setTelefono(?string $v): void    { $this->telefono      = $v; }
    public function setCorreo(?string $v): void      { $this->correo        = $v; }
    public function setDireccion(?string $v): void   { $this->direccion     = $v; }
    public function setRif(?string $v): void         { $this->rif           = $v; }
    public function setRazonSocial(?string $v): void { $this->razonSocial   = $v; }
    public function setTipoCliente(string $v): void  { $this->tipoCliente   = $v; }
    public function setLimiteCredito(float $v): void { $this->limiteCredito = $v; }
    public function setCondicionPago(string $v): void{ $this->condicionPago = $v; }
    public function setEstatus(int $v): void         { $this->estatus       = $v; }

    // ── Dispatcher ─────────────────────────────────────────────────────

    /**
     * Punto de entrada único para todas las operaciones del modelo.
     *
     * @param  array{peticion: string, ...} $peticion
     * @return array
     */
    public function Transaccion(array $peticion): array
    {
        return match ($peticion['peticion'] ?? '') {
            'registrar'          => $this->registrar(),
            'consultar'          => $this->consultar(),
            'buscar'             => $this->buscar($peticion['cedula'] ?? ''),
            'modificar'          => $this->modificar(),
            'eliminar'           => $this->eliminar(),
            'verificar_cedula'   => $this->verificarCedula(),
            'verificar_morosidad'=> $this->verificarMorosidad($peticion['cedula'] ?? $this->cedula ?? ''),
            'conteo_transacciones' => $this->conteoTransacciones($peticion['cedula'] ?? $this->cedula ?? ''),
            default              => [
                'estado'      => -1,
                'response'    => ['resultado' => 400, 'icon' => 'error', 'mensaje' => 'Petición no válida.'],
                'HTTP_STATUS' => ['codigo' => 400, 'mensaje' => 'Bad Request'],
            ],
        };
    }

    // ════════════════════════════════════════════════════════════════════
    // REGLA DE NEGOCIO 1 — Bloqueo preventivo por morosidad > 7 días
    // ════════════════════════════════════════════════════════════════════

    /**
     * Verifica si el cliente tiene facturas pendientes con más de 7 días de vencimiento.
     *
     * Si el resultado es > 0, el controlador debe devolver HTTP 403 y
     * el front-end desplegará un modal SweetAlert2 de bloqueo.
     *
     * @param  string $cedula  Cédula del cliente a verificar
     * @return array
     */
    public function verificarMorosidad(string $cedula): array
    {
        try {
            $this->LlamarConexion();

            $sql = "SELECT COUNT(id_factura) AS deudas_vencidas
                    FROM factura
                    WHERE cedula_cliente = :cedula
                      AND estado        = 'PENDIENTE'
                      AND DATEDIFF(NOW(), fecha_vencimiento) > 7";

            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute([':cedula' => $cedula]);
            $resultado = $stm->fetch(PDO::FETCH_ASSOC);

            $deudasVencidas = (int) ($resultado['deudas_vencidas'] ?? 0);
            $bloqueado      = $deudasVencidas > 0;

            return [
                'estado'      => 1,
                'bloqueado'   => $bloqueado,
                'deudas'      => $deudasVencidas,
                'response'    => [
                    'resultado' => $bloqueado ? 403 : 200,
                    'icon'      => $bloqueado ? 'warning' : 'success',
                    'mensaje'   => $bloqueado
                        ? "⚠️ Bloqueo crediticio: El cliente posee {$deudasVencidas} factura(s) con vencimiento mayor a 7 días. Gestione el cobro antes de proceder."
                        : 'Cliente sin deudas vencidas. Puede proceder.',
                ],
                'HTTP_STATUS' => ['codigo' => $bloqueado ? 403 : 200],
            ];

        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // REGLA DE NEGOCIO 2 — Probación de nuevos clientes (< 5 pedidos)
    // ════════════════════════════════════════════════════════════════════

    /**
     * Cuenta el historial de transacciones entregadas del cliente.
     *
     * Si el total < 5, el sistema fuerza condición de pago CONTADO.
     * El controlador debe rechazar cualquier intento de asignar CRÉDITO.
     *
     * @param  string $cedula  Cédula del cliente
     * @return array
     */
    public function conteoTransacciones(string $cedula): array
    {
        try {
            $this->LlamarConexion();

            $sql = "SELECT COUNT(*) AS total_pedidos
                    FROM pedido
                    WHERE cedula_cliente = :cedula
                      AND estado IN ('ENTREGADO', 'CONFIRMADO')";

            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute([':cedula' => $cedula]);
            $fila = $stm->fetch(PDO::FETCH_ASSOC);

            $total         = (int) ($fila['total_pedidos'] ?? 0);
            $enProbacion   = $total < 5;

            return [
                'estado'       => 1,
                'en_probacion' => $enProbacion,
                'total'        => $total,
                'response'     => [
                    'resultado' => 200,
                    'icon'      => $enProbacion ? 'info' : 'success',
                    'mensaje'   => $enProbacion
                        ? "ℹ️ Política comercial: Este cliente solo ha completado {$total} transacción(es). Las primeras 5 órdenes son obligatoriamente de contado."
                        : 'Cliente habilitado para crédito.',
                ],
                'HTTP_STATUS'  => ['codigo' => 200],
            ];

        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // CRUD ESTÁNDAR
    // ─────────────────────────────────────────────────────────────────

    private function registrar(): array
    {
        try {
            $this->LlamarConexion();

            // Verificar duplicado
            $stmChk = $this->LlamarConexion()->prepare("SELECT cedula FROM cliente WHERE cedula = :c LIMIT 1");
            $stmChk->execute([':c' => $this->cedula]);
            if ($stmChk->rowCount() > 0) {
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 409, 'icon' => 'error', 'mensaje' => 'El cliente ya se encuentra registrado.'],
                    'HTTP_STATUS' => ['codigo' => 409, 'mensaje' => 'Conflict'],
                ];
            }

            $this->LlamarConexion()->beginTransaction();

            // Upsert en persona
            $stmP = $this->LlamarConexion()->prepare("SELECT cedula FROM persona WHERE cedula = :c");
            $stmP->execute([':c' => $this->cedula]);

            if ($stmP->rowCount() === 0) {
                $this->LlamarConexion()->prepare(
                    "INSERT INTO persona (cedula, nombre, apellido, fecha_nac, sexo, telefono, correo, direccion)
                     VALUES (:c, :n, :a, :fn, :s, :t, :e, :d)"
                )->execute([
                    ':c' => $this->cedula, ':n' => $this->nombre, ':a' => $this->apellido,
                    ':fn'=> $this->fechaNac, ':s' => $this->sexo, ':t' => $this->telefono,
                    ':e' => $this->correo,  ':d' => $this->direccion,
                ]);
            }

            // Insertar en cliente
            $this->LlamarConexion()->prepare(
                "INSERT INTO cliente (cedula, rif, razon_social, tipo_cliente, limite_credito, condicion_pago)
                 VALUES (:c, :r, :rs, :tc, :lc, :cp)"
            )->execute([
                ':c'  => $this->cedula,       ':r'  => $this->rif,
                ':rs' => $this->razonSocial,  ':tc' => $this->tipoCliente,
                ':lc' => $this->limiteCredito,':cp' => $this->condicionPago,
            ]);

            $this->LlamarConexion()->commit();

            return [
                'estado'      => 1,
                'cedula'      => $this->cedula,
                'nombre'      => $this->nombre . ' ' . $this->apellido,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Cliente registrado exitosamente.'],
                'HTTP_STATUS' => ['codigo' => 200, 'mensaje' => 'OK'],
            ];

        } catch (\PDOException $e) {
            if ($this->LlamarConexion()->inTransaction()) $this->LlamarConexion()->rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'correo')) {
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 409, 'icon' => 'error', 'mensaje' => 'El correo electrónico ya está registrado.'],
                    'HTTP_STATUS' => ['codigo' => 409, 'mensaje' => 'Conflict'],
                ];
            }
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function consultar(): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT p.cedula, p.nombre, p.apellido, p.telefono, p.correo, p.direccion,
                           c.rif, c.razon_social, c.tipo_cliente, c.limite_credito,
                           c.condicion_pago, c.estatus, c.fecha_registro
                    FROM persona p
                    INNER JOIN cliente c ON p.cedula = c.cedula
                    WHERE c.estatus = 1
                    ORDER BY p.apellido, p.nombre";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute();
            $datos = $stm->fetchAll(PDO::FETCH_ASSOC);

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'mensaje' => 'OK', 'datos' => $datos],
                'HTTP_STATUS' => ['codigo' => 200, 'mensaje' => 'OK'],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function buscar(string $cedula): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT p.*, c.rif, c.razon_social, c.tipo_cliente,
                           c.limite_credito, c.condicion_pago, c.estatus, c.fecha_registro
                    FROM persona p
                    INNER JOIN cliente c ON p.cedula = c.cedula
                    WHERE p.cedula = :c LIMIT 1";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute([':c' => $cedula]);
            $dato = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$dato) {
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 404, 'icon' => 'error', 'mensaje' => 'Cliente no encontrado.'],
                    'HTTP_STATUS' => ['codigo' => 404, 'mensaje' => 'Not Found'],
                ];
            }

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'mensaje' => 'OK', 'datos' => $dato],
                'HTTP_STATUS' => ['codigo' => 200, 'mensaje' => 'OK'],
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
                "UPDATE persona SET nombre = :n, apellido = :a, fecha_nac = :fn,
                 sexo = :s, telefono = :t, correo = :e, direccion = :d
                 WHERE cedula = :c"
            )->execute([
                ':c' => $this->cedula, ':n' => $this->nombre, ':a' => $this->apellido,
                ':fn'=> $this->fechaNac, ':s' => $this->sexo, ':t' => $this->telefono,
                ':e' => $this->correo,  ':d' => $this->direccion,
            ]);

            $this->LlamarConexion()->prepare(
                "UPDATE cliente SET rif = :r, razon_social = :rs, tipo_cliente = :tc,
                 limite_credito = :lc, condicion_pago = :cp WHERE cedula = :c"
            )->execute([
                ':c'  => $this->cedula,       ':r'  => $this->rif,
                ':rs' => $this->razonSocial,  ':tc' => $this->tipoCliente,
                ':lc' => $this->limiteCredito,':cp' => $this->condicionPago,
            ]);

            $this->LlamarConexion()->commit();

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Cliente actualizado exitosamente.'],
                'HTTP_STATUS' => ['codigo' => 200, 'mensaje' => 'OK'],
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
            $this->LlamarConexion()->prepare("UPDATE cliente SET estatus = 0 WHERE cedula = :c")
                ->execute([':c' => $this->cedula]);

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Cliente deshabilitado exitosamente.'],
                'HTTP_STATUS' => ['codigo' => 200, 'mensaje' => 'OK'],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function verificarCedula(): array
    {
        try {
            $this->LlamarConexion();
            $stm = $this->LlamarConexion()->prepare("SELECT cedula FROM cliente WHERE cedula = :c LIMIT 1");
            $stm->execute([':c' => $this->cedula]);
            $existe = $stm->rowCount() > 0;

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'existe' => $existe],
                'HTTP_STATUS' => ['codigo' => 200, 'mensaje' => 'OK'],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    // ── Helpers internos ───────────────────────────────────────────────

    private function respuestaError500(): array
    {
        return [
            'estado'      => -1,
            'response'    => ['resultado' => 500, 'icon' => 'error', 'mensaje' => 'Error interno del servidor. Intente de nuevo.'],
            'HTTP_STATUS' => ['codigo' => 500, 'mensaje' => 'Internal Server Error'],
        ];
    }
}
