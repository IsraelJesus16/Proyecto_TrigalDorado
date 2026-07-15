<?php
namespace App\Controllers;

use App\Models\Cliente;
use App\Helpers\Helper;

// API endpoints
$action = $_REQUEST['action'] ?? null;
$type   = $_REQUEST['type'] ?? 'index';

if ($action) {
    header('Content-Type: application/json');
    $cliente = new Cliente();

    if ($action === 'consultar') {
        echo json_encode($cliente->Transaccion(['peticion' => 'consultar']));
        return;
    }

    if ($action === 'buscar' && isset($_POST['cedula'])) {
        echo json_encode($cliente->Transaccion(['peticion' => 'buscar', 'cedula' => $_POST['cedula']]));
        return;
    }

    if ($action === 'guardar') {
        $cedula = $_POST['cedula'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $apellido = $_POST['apellido'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        
        // Backend Validations
        if (!preg_match('/^(V|E|J|G)-[0-9]{5,9}$/', $cedula)) {
            echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'Formato de cédula inválido']]);
            return;
        }
        if (preg_match('/[0-9]/', $nombre) || preg_match('/[0-9]/', $apellido)) {
            echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'Nombre y apellido no pueden contener números']]);
            return;
        }
        if (strlen($telefono) > 12) {
            echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'El teléfono excede la longitud permitida']]);
            return;
        }

        $cliente->setCedula($cedula);
        $cliente->setNombre(trim(mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8")));
        $cliente->setApellido(trim(mb_convert_case($apellido, MB_CASE_TITLE, "UTF-8")));
        $cliente->setCorreo($_POST['correo'] ?? '');
        $cliente->setTelefono($telefono);
        $cliente->setDireccion($_POST['direccion'] ?? '');
        
        $cliente->setTipoCliente($_POST['tipo_cliente'] ?? 'NATURAL');
        if (($_POST['tipo_cliente'] ?? '') === 'JURIDICO') {
            $cliente->setRif($_POST['rif'] ?? '');
            $cliente->setRazonSocial($_POST['razon_social'] ?? '');
        }

        $cliente->setCondicionPago($_POST['condicion_pago'] ?? 'CONTADO');
        $cliente->setLimiteCredito((float)($_POST['limite_credito'] ?? 0));

        if (!empty($_POST['es_edicion'])) {
            echo json_encode($cliente->Transaccion(['peticion' => 'modificar']));
        } else {
            echo json_encode($cliente->Transaccion(['peticion' => 'registrar']));
        }
        return;
    }

    if ($action === 'eliminar' && isset($_POST['cedula'])) {
        $cliente->setCedula($_POST['cedula']);
        echo json_encode($cliente->Transaccion(['peticion' => 'eliminar']));
        return;
    }

    echo json_encode(['estado' => -1, 'response' => ['mensaje' => 'Acción no válida']]);
    return;
}

// Vista
if ($type === 'index') {
    $titulo = 'Directorio de Clientes — El Trigal Dorado';
    require_once BASE_PATH . '/resources/views/admin/clientes/index.php';
    return;
}
