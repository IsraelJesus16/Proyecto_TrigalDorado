<?php
namespace App\Controllers;

use App\Models\Usuario;
use App\Helpers\Helper;

// API endpoints
$action = $_REQUEST['action'] ?? null;
$type   = $_REQUEST['type'] ?? 'index';

if ($action) {
    header('Content-Type: application/json');
    $usuario = new Usuario();

    if ($action === 'consultar') {
        echo json_encode($usuario->Transaccion(['peticion' => 'consultar']));
        return;
    }

    if ($action === 'buscar' && isset($_POST['id_usuario'])) {
        echo json_encode($usuario->Transaccion(['peticion' => 'buscar', 'id_usuario' => $_POST['id_usuario']]));
        return;
    }

    // ── Protección: IDs que nunca se pueden modificar o eliminar ──────
    $USUARIOS_PROTEGIDOS = ['USR-ADMIN-00000001'];
    $idSesionActual      = $_SESSION['usuario']['id_usuario'] ?? '';

    if ($action === 'guardar') {
        $idEditando = trim($_POST['id_usuario'] ?? '');

        // Bloquear edición de usuario protegido por otro usuario que no sea él mismo
        if ($idEditando && (in_array($idEditando, $USUARIOS_PROTEGIDOS) && $idEditando !== $idSesionActual)) {
            echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'Este usuario del sistema no puede ser modificado.']]);
            return;
        }

        $usuario->setCedula($_POST['cedula'] ?? '');
        $usuario->setIdRol($_POST['id_rol'] ?? '');
        $usuario->setUsername($_POST['username'] ?? '');
        $usuario->setNombre($_POST['nombre'] ?? '');
        $usuario->setApellido($_POST['apellido'] ?? '');
        $usuario->setCorreo($_POST['correo'] ?? '');
        
        // La contraseña solo se actualiza si se envía
        if (!empty($_POST['password'])) {
            $usuario->setPasswordHash(password_hash($_POST['password'], PASSWORD_DEFAULT, ['cost' => 12]));
        }

        if (!empty($_POST['id_usuario'])) {
            $usuario->setIdUsuario($_POST['id_usuario']);
            echo json_encode($usuario->Transaccion(['peticion' => 'modificar']));
        } else {
            echo json_encode($usuario->Transaccion(['peticion' => 'registrar']));
        }
        return;
    }

    if ($action === 'eliminar' && isset($_POST['id_usuario'])) {
        $idTarget = trim($_POST['id_usuario']);
        if (in_array($idTarget, $USUARIOS_PROTEGIDOS) || $idTarget === $idSesionActual) {
            echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'No puedes deshabilitar este usuario.']]);
            return;
        }
        $usuario->setIdUsuario($idTarget);
        echo json_encode($usuario->Transaccion(['peticion' => 'eliminar']));
        return;
    }

    if ($action === 'cambiar_estatus' && isset($_POST['id_usuario'])) {
        $idTarget = trim($_POST['id_usuario']);
        if (in_array($idTarget, $USUARIOS_PROTEGIDOS) || $idTarget === $idSesionActual) {
            echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'No puedes cambiar el estatus de este usuario.']]);
            return;
        }
        $usuario->setIdUsuario($idTarget);
        $usuario->setEstatus((int)($_POST['estatus'] ?? 0));
        echo json_encode($usuario->Transaccion(['peticion' => 'cambiar_estatus']));
        return;
    }

    echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'Acción no válida']]);
    return;
}

// Vista
if ($type === 'index') {
    $titulo = 'Gestión de Usuarios — El Trigal Dorado';
    
    // Obtener roles para el select
    $db = new \App\Core\Database();
    $db->LlamarConexion();
    $stmt = $db->LlamarConexion()->query("SELECT id_rol, nombre FROM rol WHERE estatus = 1 ORDER BY nombre");
    $roles = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    $db->DestruirConexion();

    require_once BASE_PATH . '/resources/views/admin/usuarios/index.php';
    return;
}
