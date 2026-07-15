<?php

namespace App\Controllers;

use App\Models\Rol;
use App\Helpers\Helper;

$type = $_REQUEST['type'] ?? 'index';

// ─── Control de Acceso General ──────────────────────────────────────────
$usuario = $_SESSION['usuario'] ?? [];
$permisos = $usuario['permisos'] ?? [];

if (!in_array('PERM_ROL_GESTIONAR', $permisos, true)) {
    Helper::redirigir('/?page=Dashboard');
    return;
}

$modeloRol = new Rol();

switch ($type) {
    case 'index':
        $titulo = 'Gestión de Roles — El Trigal Dorado';
        $roles = $modeloRol->obtenerTodos();
        $permisosDisponibles = $modeloRol->obtenerTodosLosPermisos();

        // Agrupar permisos por módulo para la vista
        $permisosPorModulo = [];
        foreach ($permisosDisponibles as $p) {
            $permisosPorModulo[$p['modulo']][] = $p;
        }

        require_once BASE_PATH . '/resources/views/admin/roles/index.php';
        break;

    case 'ajax':
        header('Content-Type: application/json');
        $peticion = $_POST['peticion'] ?? '';

        if (!Helper::validarCSRF($_POST['csrf_token'] ?? '')) {
            echo json_encode(['resultado' => 403, 'mensaje' => 'Token CSRF inválido.']);
            exit;
        }

        if ($peticion === 'obtener_rol') {
            $id = $_POST['id_rol'] ?? '';
            $rol = $modeloRol->obtenerPorId($id);
            if ($rol) {
                $rol['permisos'] = array_column($modeloRol->obtenerPermisosPorRol($id), 'id_permiso');
                echo json_encode(['resultado' => 200, 'rol' => $rol]);
            } else {
                echo json_encode(['resultado' => 404, 'mensaje' => 'Rol no encontrado.']);
            }
            exit;
        }

        if ($peticion === 'guardar') {
            $idRolViejo = trim($_POST['id_rol_viejo'] ?? '');
            $idRolNuevo = trim($_POST['id_rol'] ?? '');
            $nombre     = trim($_POST['nombre'] ?? '');
            $desc       = trim($_POST['descripcion'] ?? '');
            $permisosReq= json_decode($_POST['permisos'] ?? '[]', true);

            if (empty($idRolNuevo) || empty($nombre)) {
                echo json_encode(['resultado' => 400, 'mensaje' => 'El ID del rol y el nombre son obligatorios.']);
                exit;
            }

            // Normalizar ID del rol (ej: ROL_GERENTE)
            if (strpos($idRolNuevo, 'ROL_') !== 0) {
                $idRolNuevo = 'ROL_' . strtoupper($idRolNuevo);
            }
            $idRolNuevo = preg_replace('/[^A-Z0-9_]/', '', $idRolNuevo);

            try {
                if (empty($idRolViejo)) {
                    // Crear nuevo
                    // Validar si ya existe
                    if ($modeloRol->obtenerPorId($idRolNuevo)) {
                        echo json_encode(['resultado' => 409, 'mensaje' => 'El ID de rol ya existe.']);
                        exit;
                    }
                    $modeloRol->crear($idRolNuevo, $nombre, $desc, $permisosReq);
                    echo json_encode(['resultado' => 200, 'mensaje' => 'Rol creado correctamente.']);
                } else {
                    // Actualizar
                    // Proteger roles críticos
                    if ($idRolViejo === 'ROL_SUPERADMIN') {
                        echo json_encode(['resultado' => 403, 'mensaje' => 'No se puede modificar el rol SuperAdmin.']);
                        exit;
                    }
                    $modeloRol->actualizar($idRolViejo, $idRolNuevo, $nombre, $desc, $permisosReq);

                    // Si el usuario actual tiene este rol, actualizar sus permisos en sesión
                    if (($_SESSION['usuario']['id_rol'] ?? '') === $idRolNuevo) {
                        $_SESSION['usuario']['permisos'] = $permisosReq;
                    }

                    echo json_encode(['resultado' => 200, 'mensaje' => 'Rol actualizado correctamente.']);
                }
            } catch (\Exception $e) {
                echo json_encode(['resultado' => 500, 'mensaje' => 'Error de base de datos: ' . $e->getMessage()]);
            }
            exit;
        }

        if ($peticion === 'eliminar') {
            $id = $_POST['id_rol'] ?? '';

            if ($id === 'ROL_SUPERADMIN') {
                echo json_encode(['resultado' => 403, 'mensaje' => 'No se puede eliminar el rol SuperAdmin.']);
                exit;
            }

            try {
                $modeloRol->eliminar($id);
                echo json_encode(['resultado' => 200, 'mensaje' => 'Rol eliminado correctamente.']);
            } catch (\Exception $e) {
                echo json_encode(['resultado' => 409, 'mensaje' => $e->getMessage()]);
            }
            exit;
        }

        echo json_encode(['resultado' => 400, 'mensaje' => 'Petición inválida.']);
        exit;
        break;

    default:
        require_once BASE_PATH . '/resources/views/errors/404.php';
        break;
}
