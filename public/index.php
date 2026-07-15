<?php

require_once __DIR__ . '/../vendor/autoload.php';

// ── Constantes globales ──────────────────────────────────────────────
define('DS',        DIRECTORY_SEPARATOR);
define('BASE_PATH', realpath(__DIR__ . '/..'));

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

define('BASE_URL', rtrim($protocol . $host . $basePath, '/\\') . '/');

// ── Entorno ──────────────────────────────────────────────────────────
ini_set('display_errors',         1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Caracas');

// ── Sesión ───────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Token CSRF: se genera una vez por sesión
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Front Controller ─────────────────────────────────────────────────
use App\Controllers\FrontController;

try {
    $frontController = new FrontController();

} catch (Throwable $e) {

    $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
              && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($esAjax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'resultado' => 500,
            'mensaje'   => 'Error crítico del sistema.',
            'debug'     => [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea'   => $e->getLine(),
            ],
        ]);
        exit;
    }

    http_response_code(500);
    echo "<h1>Error en la aplicación</h1>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
