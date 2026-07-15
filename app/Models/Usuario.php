<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Helper;
use PDO;

/**
 * Usuario — Modelo de autenticación y gestión de usuarios del sistema.
 */
class Usuario extends Database
{
    private ?string $idUsuario     = null;
    private ?string $cedula        = null;
    private ?string $idRol         = null;
    private ?string $username      = null;
    private ?string $nombre        = null;
    private ?string $apellido      = null;
    private ?string $correo        = null;
    private ?string $password      = null; // Texto plano antes de hash
    private ?string $passwordHash  = null;
    private int     $estatus       = 1;
    private ?string $fotoPerfil    = null;

    // Setters
    public function setIdUsuario(?string $v): void    { $this->idUsuario    = $v; }
    public function setCedula(string $v): void        { $this->cedula       = trim($v); }
    public function setIdRol(string $v): void         { $this->idRol        = trim($v); }
    public function setUsername(string $v): void      { $this->username     = trim($v); }
    public function setNombre(string $v): void        { $this->nombre       = trim($v); }
    public function setApellido(string $v): void      { $this->apellido     = trim($v); }
    public function setCorreo(string $v): void        { $this->correo       = trim($v) ?: null; }
    public function setPassword(string $v): void      { $this->password     = $v; }
    public function setPasswordHash(string $v): void  { $this->passwordHash = $v; }
    public function setEstatus(int $v): void          { $this->estatus      = $v ? 1 : 0; }
    public function setFotoPerfil(?string $v): void   { $this->fotoPerfil   = $v; }

    public function Transaccion(array $peticion): array
    {
        return match ($peticion['peticion'] ?? '') {
            'login'          => $this->login($peticion['username'] ?? '', $peticion['password'] ?? ''),
            'registrar'      => $this->registrar(),
            'consultar'      => $this->consultar(),
            'perfil'         => $this->perfil($peticion['id_usuario'] ?? $_SESSION['usuario']['id_usuario'] ?? ''),
            'modificar'      => $this->modificar(),
            'eliminar'       => $this->eliminar(),
            'cambiar_estatus'=> $this->cambiarEstatus(),
            'cambiar_clave'  => $this->cambiarClave($peticion['clave_actual'] ?? '', $peticion['nueva_clave'] ?? ''),
            default          => [
                'estado'      => -1,
                'response'    => ['resultado' => 400, 'icon' => 'error', 'mensaje' => 'Petición no válida.'],
                'HTTP_STATUS' => ['codigo' => 400],
            ],
        };
    }

    /**
     * Autentica al usuario y carga sus datos + permisos en sesión.
     */
    private function login(string $username, string $password): array
    {
        try {
            $this->LlamarConexion();

            $sql = "SELECT u.id_usuario, u.cedula, u.id_rol, u.username,
                           u.password_hash, u.estatus, u.foto_perfil,
                           r.nombre AS rol,
                           p.nombre, p.apellido, p.correo, p.telefono
                    FROM usuario u
                    INNER JOIN rol r    ON u.id_rol = r.id_rol
                    INNER JOIN persona p ON u.cedula = p.cedula
                    WHERE (u.username = :usu1 OR u.cedula = :usu2)
                    LIMIT 1";

            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute([':usu1' => $username, ':usu2' => $username]);
            $usuario = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 401, 'icon' => 'error', 'mensaje' => 'Usuario o contraseña incorrectos.'],
                    'HTTP_STATUS' => ['codigo' => 401],
                ];
            }

            if ($usuario['estatus'] != 1) {
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 403, 'icon' => 'warning', 'mensaje' => 'Tu cuenta está deshabilitada. Contacta al administrador.'],
                    'HTTP_STATUS' => ['codigo' => 403],
                ];
            }

            if (!password_verify($password, $usuario['password_hash'])) {
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 401, 'icon' => 'error', 'mensaje' => 'Usuario o contraseña incorrectos.'],
                    'HTTP_STATUS' => ['codigo' => 401],
                ];
            }

            // Cargar permisos del rol
            $sqlPerm = "SELECT rp.id_permiso FROM rol_permiso rp WHERE rp.id_rol = :rol";
            $stmPerm = $this->LlamarConexion()->prepare($sqlPerm);
            $stmPerm->execute([':rol' => $usuario['id_rol']]);
            $permisos = $stmPerm->fetchAll(PDO::FETCH_COLUMN, 0);

            // Actualizar último acceso
            $this->LlamarConexion()->prepare(
                "UPDATE usuario SET ultimo_acceso = NOW() WHERE id_usuario = :id"
            )->execute([':id' => $usuario['id_usuario']]);

            // Guardar en sesión
            unset($usuario['password_hash']); // Nunca guardar el hash en sesión
            $usuario['permisos'] = $permisos;
            $_SESSION['usuario'] = $usuario;

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => "Bienvenido, {$usuario['nombre']}!"],
                'HTTP_STATUS' => ['codigo' => 200],
            ];

        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function registrar(): array
    {
        try {
            $this->LlamarConexion();
            $this->LlamarConexion()->beginTransaction();

            // Verificar username único
            $stmChk = $this->LlamarConexion()->prepare("SELECT id_usuario FROM usuario WHERE username = :u LIMIT 1");
            $stmChk->execute([':u' => $this->username]);
            if ($stmChk->rowCount() > 0) {
                $this->LlamarConexion()->rollBack();
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 409, 'icon' => 'error', 'mensaje' => 'El nombre de usuario ya está en uso.'],
                    'HTTP_STATUS' => ['codigo' => 409],
                ];
            }

            // Insertar o actualizar persona
            $this->LlamarConexion()->prepare(
                "INSERT INTO persona (cedula, nombre, apellido, correo)
                 VALUES (:c, :n, :a, :co)
                 ON DUPLICATE KEY UPDATE nombre=:n, apellido=:a, correo=COALESCE(:co, correo)"
            )->execute([':c' => $this->cedula, ':n' => $this->nombre, ':a' => $this->apellido, ':co' => $this->correo]);

            $hash = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 12]);
            $id   = Helper::uuid();

            $this->LlamarConexion()->prepare(
                "INSERT INTO usuario (id_usuario, cedula, id_rol, username, password_hash)
                 VALUES (:id, :c, :rol, :u, :h)"
            )->execute([
                ':id'  => $id, ':c'  => $this->cedula, ':rol' => $this->idRol,
                ':u'   => $this->username, ':h' => $hash,
            ]);

            $this->LlamarConexion()->commit();
            return [
                'estado'      => 1,
                'id_usuario'  => $id,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Usuario registrado exitosamente.'],
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

    private function consultar(): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT u.id_usuario, u.cedula, u.username, u.estatus, u.ultimo_acceso,
                           u.foto_perfil, r.nombre AS rol, r.id_rol,
                           p.nombre, p.apellido, p.correo, p.telefono
                    FROM usuario u
                    INNER JOIN rol r    ON u.id_rol = r.id_rol
                    INNER JOIN persona p ON u.cedula = p.cedula
                    ORDER BY p.apellido, p.nombre";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute();
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

    private function perfil(string $idUsuario): array
    {
        try {
            $this->LlamarConexion();
            $sql = "SELECT u.id_usuario, u.cedula, u.username, u.estatus, u.foto_perfil,
                           r.id_rol, r.nombre AS rol,
                           p.nombre, p.apellido, p.correo, p.telefono, p.direccion
                    FROM usuario u
                    INNER JOIN rol r    ON u.id_rol = r.id_rol
                    INNER JOIN persona p ON u.cedula = p.cedula
                    WHERE u.id_usuario = :id LIMIT 1";
            $stm = $this->LlamarConexion()->prepare($sql);
            $stm->execute([':id' => $idUsuario]);
            $dato = $stm->fetch(PDO::FETCH_ASSOC);
            return [
                'estado'      => $dato ? 1 : -1,
                'response'    => ['resultado' => $dato ? 200 : 404, 'datos' => $dato],
                'HTTP_STATUS' => ['codigo' => $dato ? 200 : 404],
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

            // Actualizar datos de persona
            if ($this->nombre || $this->apellido || $this->correo) {
                $setPers = [];
                $paramsPers = [':c' => $this->cedula];
                if ($this->nombre)   { $setPers[] = 'nombre = :n';  $paramsPers[':n']  = $this->nombre; }
                if ($this->apellido) { $setPers[] = 'apellido = :a'; $paramsPers[':a'] = $this->apellido; }
                if ($this->correo !== null)   { $setPers[] = 'correo = :co'; $paramsPers[':co'] = $this->correo; }
                if ($setPers) {
                    $this->LlamarConexion()->prepare(
                        "UPDATE persona SET " . implode(', ', $setPers) . " WHERE cedula = :c"
                    )->execute($paramsPers);
                }
            }

            // Actualizar datos de usuario
            $sets = "id_rol = :rol";
            $params = [':rol' => $this->idRol, ':id' => $this->idUsuario];
            if ($this->fotoPerfil) { $sets .= ', foto_perfil = :fp'; $params[':fp'] = $this->fotoPerfil; }
            $this->LlamarConexion()->prepare("UPDATE usuario SET {$sets} WHERE id_usuario = :id")->execute($params);

            // Actualizar contraseña si se proveyó
            if ($this->passwordHash) {
                $this->LlamarConexion()->prepare(
                    "UPDATE usuario SET password_hash = :h WHERE id_usuario = :id"
                )->execute([':h' => $this->passwordHash, ':id' => $this->idUsuario]);
            }

            $this->LlamarConexion()->commit();
            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Usuario actualizado correctamente.'],
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
            $this->LlamarConexion()->prepare("UPDATE usuario SET estatus = 0 WHERE id_usuario = :id")
                ->execute([':id' => $this->idUsuario]);
            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Usuario deshabilitado.'],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function cambiarEstatus(): array
    {
        try {
            $this->LlamarConexion();
            $nuevoEstatus = $this->estatus ? 1 : 0;
            $this->LlamarConexion()->prepare("UPDATE usuario SET estatus = :est WHERE id_usuario = :id")
                ->execute([':est' => $nuevoEstatus, ':id' => $this->idUsuario]);
            $msg = $nuevoEstatus ? 'Usuario habilitado correctamente.' : 'Usuario deshabilitado correctamente.';
            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => $msg],
                'HTTP_STATUS' => ['codigo' => 200],
            ];
        } catch (\PDOException $e) {
            Helper::ErrorLog($e->getMessage() . " en " . __METHOD__);
            return $this->respuestaError500();
        } finally {
            $this->DestruirConexion();
        }
    }

    private function cambiarClave(string $claveActual, string $nuevaClave): array
    {
        try {
            $this->LlamarConexion();
            $stm = $this->LlamarConexion()->prepare(
                "SELECT password_hash FROM usuario WHERE id_usuario = :id LIMIT 1"
            );
            $stm->execute([':id' => $this->idUsuario ?? $_SESSION['usuario']['id_usuario']]);
            $fila = $stm->fetch(PDO::FETCH_ASSOC);

            if (!$fila || !password_verify($claveActual, $fila['password_hash'])) {
                return [
                    'estado'      => -1,
                    'response'    => ['resultado' => 401, 'icon' => 'error', 'mensaje' => 'La clave actual es incorrecta.'],
                    'HTTP_STATUS' => ['codigo' => 401],
                ];
            }

            $nuevoHash = password_hash($nuevaClave, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->LlamarConexion()->prepare(
                "UPDATE usuario SET password_hash = :h WHERE id_usuario = :id"
            )->execute([':h' => $nuevoHash, ':id' => $this->idUsuario ?? $_SESSION['usuario']['id_usuario']]);

            return [
                'estado'      => 1,
                'response'    => ['resultado' => 200, 'icon' => 'success', 'mensaje' => 'Contraseña actualizada exitosamente.'],
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
            'response'    => ['resultado' => 500, 'icon' => 'error', 'mensaje' => 'Error interno. Intente de nuevo.'],
            'HTTP_STATUS' => ['codigo' => 500],
        ];
    }
}
