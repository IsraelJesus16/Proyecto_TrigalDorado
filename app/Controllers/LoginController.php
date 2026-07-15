<?php

namespace App\Controllers;

use App\Models\Usuario;
use App\Helpers\Helper;

$type = $_REQUEST['type'] ?? 'index';

if ($type === 'index') {

    // Ya autenticado → redirigir al dashboard
    if (Helper::estaAutenticado()) {
        Helper::redirigir('/?page=Dashboard');
        return;
    }

    $error = $_SESSION['error_login'] ?? null;
    unset($_SESSION['error_login']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Validar CSRF
        $tokenPost = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $tokenPost)) {
            if (Helper::esAjax()) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['resultado' => 403, 'mensaje' => 'Token de seguridad inválido. Recarga la página.']);
                return;
            }
            $error = 'Token de seguridad inválido. Recarga la página.';
        } else {
            $peticion = $_POST['peticion'] ?? '';

            if ($peticion === 'login') {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($username) || empty($password)) {
                    if (Helper::esAjax()) {
                        header('Content-Type: application/json');
                        echo json_encode(['resultado' => 400, 'mensaje' => 'Por favor, completa todos los campos.']);
                        return;
                    }
                    $error = 'Por favor, completa todos los campos.';
                } else {
                    $usuarioModel = new Usuario();
                    $resultado    = $usuarioModel->Transaccion([
                        'peticion' => 'login',
                        'username' => $username,
                        'password' => $password,
                    ]);

                    if ($resultado['estado'] === 1) {
                        if (Helper::esAjax()) {
                            header('Content-Type: application/json');
                            echo json_encode($resultado['response']);
                            return;
                        }
                        Helper::redirigir('/?page=Dashboard');
                        return;
                    }

                    if (Helper::esAjax()) {
                        header('Content-Type: application/json');
                        http_response_code($resultado['HTTP_STATUS']['codigo'] ?? 401);
                        echo json_encode(['resultado' => $resultado['HTTP_STATUS']['codigo'] ?? 401, 'mensaje' => $resultado['response']['mensaje'] ?? 'Error al iniciar sesión.']);
                        return;
                    }
                    $error = $resultado['response']['mensaje'] ?? 'Error al iniciar sesión.';
                }
            }
        }
    }

    $titulo = 'Iniciar Sesión — El Trigal Dorado';
    require_once BASE_PATH . '/resources/views/auth/login.php';
}
