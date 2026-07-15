<?php

namespace App\Helpers;

/**
 * Helper — Clase de utilidades estáticas del sistema.
 */
class Helper
{
    /**
     * Registra un error en el log del sistema.
     */
    public static function ErrorLog(string $mensaje): void
    {
        $fecha  = date('Y-m-d H:i:s');
        $logDir = BASE_PATH . '/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $archivo = $logDir . '/error_' . date('Y-m-d') . '.log';
        file_put_contents($archivo, "[{$fecha}] {$mensaje}" . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Valida el token CSRF enviado en una petición.
     */
    public static function validarCSRF(string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Genera un ID único tipo UUID v4.
     */
    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Verifica si una petición es AJAX (XMLHttpRequest o fetch).
     */
    public static function esAjax(): bool
    {
        $xrw = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return $xrw === 'xmlhttprequest';
    }

    /**
     * Verifica si el usuario tiene sesión activa.
     */
    public static function estaAutenticado(): bool
    {
        return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id_usuario']);
    }

    /**
     * Verifica si el usuario tiene un permiso específico.
     */
    public static function tienePermiso(string $idPermiso): bool
    {
        $permisos = $_SESSION['usuario']['permisos'] ?? [];
        return in_array($idPermiso, $permisos, true);
    }

    /**
     * Redirige a una URL usando BASE_URL.
     */
    public static function redirigir(string $ruta): void
    {
        header('Location: ' . rtrim(BASE_URL, '/') . $ruta);
        exit;
    }

    /**
     * Sanitiza una cadena para salida HTML.
     */
    public static function e(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sube una imagen al directorio especificado con validación.
     *
     * @param  array  $file      $_FILES['campo']
     * @param  string $directorio Ruta relativa desde public/assets/img/
     * @return array{ok: bool, nombre: string, error: string}
     */
    public static function subirImagen(array $file, string $directorio = 'productos'): array
    {
        $dirBase = BASE_PATH . '/public/assets/img/' . $directorio;

        if (!is_dir($dirBase)) {
            mkdir($dirBase, 0755, true);
        }

        // Validar que sea una imagen
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo           = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType        = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $tiposPermitidos, true)) {
            return ['ok' => false, 'nombre' => '', 'error' => 'Tipo de archivo no permitido. Solo JPG, PNG, WEBP o GIF.'];
        }

        // Validar tamaño máximo (5 MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['ok' => false, 'nombre' => '', 'error' => 'El archivo excede el tamaño máximo de 5 MB.'];
        }

        // Generar nombre único
        $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombreDisco = self::uuid() . '.' . strtolower($ext);
        $rutaFinal   = $dirBase . '/' . $nombreDisco;

        if (!move_uploaded_file($file['tmp_name'], $rutaFinal)) {
            return ['ok' => false, 'nombre' => '', 'error' => 'Error al guardar el archivo en el servidor.'];
        }

        return [
            'ok'         => true,
            'nombre'     => $nombreDisco,
            'nombre_orig'=> $file['name'],
            'ruta'       => 'assets/img/' . $directorio . '/' . $nombreDisco,
            'mime_type'  => $mimeType,
            'tamanio'    => $file['size'],
            'error'      => '',
        ];
    }
}
