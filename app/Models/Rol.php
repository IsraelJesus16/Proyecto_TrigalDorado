<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class Rol {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene todos los roles.
     */
    public function obtenerTodos(): array {
        $sql = "SELECT id_rol, nombre, descripcion FROM rol ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un rol por su ID.
     */
    public function obtenerPorId(string $idRol): ?array {
        $sql = "SELECT id_rol, nombre, descripcion FROM rol WHERE id_rol = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idRol]);
        $rol = $stmt->fetch(PDO::FETCH_ASSOC);
        return $rol ?: null;
    }

    /**
     * Obtiene todos los permisos disponibles en el sistema.
     */
    public function obtenerTodosLosPermisos(): array {
        $sql = "SELECT id_permiso, modulo, accion, descripcion FROM permiso ORDER BY modulo ASC, accion ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los permisos asignados a un rol específico.
     */
    public function obtenerPermisosPorRol(string $idRol): array {
        $sql = "SELECT p.id_permiso, p.modulo, p.accion
                FROM rol_permiso rp
                INNER JOIN permiso p ON rp.id_permiso = p.id_permiso
                WHERE rp.id_rol = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idRol]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo rol dinámico y le asigna los permisos indicados.
     * Usa transacción ACID.
     */
    public function crear(string $idRol, string $nombre, string $descripcion, array $permisos): bool {
        try {
            $this->db->beginTransaction();

            // Insertar rol
            $sqlRol = "INSERT INTO rol (id_rol, nombre, descripcion) VALUES (:id, :nom, :desc)";
            $stmtRol = $this->db->prepare($sqlRol);
            $stmtRol->execute([
                'id' => $idRol,
                'nom' => $nombre,
                'desc' => $descripcion
            ]);

            // Asignar permisos
            if (!empty($permisos)) {
                $sqlPerm = "INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (:rol, :perm)";
                $stmtPerm = $this->db->prepare($sqlPerm);
                foreach ($permisos as $idPermiso) {
                    $stmtPerm->execute([
                        'rol' => $idRol,
                        'perm' => $idPermiso
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Error creando rol: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza un rol y sus permisos.
     * Usa transacción ACID.
     */
    public function actualizar(string $idRolViejo, string $idRolNuevo, string $nombre, string $descripcion, array $permisos): bool {
        try {
            $this->db->beginTransaction();

            // Actualizar rol
            // Nota: id_rol es llave primaria y puede estar vinculada a usuarios.
            // Si cambia el ID (aunque no es recomendado en UI), actualizamos en cascada.
            // Para simplificar, asumiremos que no se cambia el ID, o que la DB tiene ON UPDATE CASCADE.
            $sqlRol = "UPDATE rol SET id_rol = :idn, nombre = :nom, descripcion = :desc WHERE id_rol = :idv";
            $stmtRol = $this->db->prepare($sqlRol);
            $stmtRol->execute([
                'idn' => $idRolNuevo,
                'nom' => $nombre,
                'desc' => $descripcion,
                'idv' => $idRolViejo
            ]);

            // Revocar permisos viejos
            $sqlDelete = "DELETE FROM rol_permiso WHERE id_rol = :id";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute(['id' => $idRolNuevo]);

            // Asignar permisos nuevos
            if (!empty($permisos)) {
                $sqlPerm = "INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (:rol, :perm)";
                $stmtPerm = $this->db->prepare($sqlPerm);
                foreach ($permisos as $idPermiso) {
                    $stmtPerm->execute([
                        'rol' => $idRolNuevo,
                        'perm' => $idPermiso
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Error actualizando rol: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un rol.
     */
    public function eliminar(string $idRol): bool {
        // Verificar si hay usuarios con este rol
        $sqlCheck = "SELECT COUNT(*) FROM usuario WHERE id_rol = :id";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['id' => $idRol]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception('No se puede eliminar un rol que tiene usuarios asignados.');
        }

        $sql = "DELETE FROM rol WHERE id_rol = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $idRol]);
    }

}
