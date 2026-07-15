<?php

namespace App\Controllers;

use App\Helpers\Helper;


class FrontController
{
    /** Páginas que no requieren autenticación */
    private const PAGINAS_PUBLICAS = [
        'Home', 'Login', 'Catalogo', 'Logout'
    ];

    private string $dir;
    private string $sufijo = 'Controller.php';

    public function __construct()
    {
        $this->dir = BASE_PATH . '/app/Controllers/';

        // Página por defecto: panel público
        $pagina = $_REQUEST['page'] ?? 'Home';

        // Normalizar slugs con guiones a PascalCase
        $pagina = $this->normalizarRuta($pagina);

        // Verificar autenticación para páginas privadas
        if (!in_array($pagina, self::PAGINAS_PUBLICAS, true)) {
            if (!Helper::estaAutenticado()) {
                // Si es una petición AJAX, devolver JSON 401
                if (Helper::esAjax()) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode([
                        'resultado' => 401,
                        'mensaje'   => 'No autorizado. Por favor, inicia sesión.',
                    ]);
                    exit;
                }
                // Redirigir al login
                Helper::redirigir('/?page=Login');
                return;
            }
        }

        $this->despacharControlador($pagina);
    }

    /**
     * Normaliza el slug de la URL a PascalCase para encontrar el controlador.
     */
    private function normalizarRuta(string $pagina): string
    {
        // Mapa de slugs con guiones o alias especiales
        $mapa = [
            ''              => 'Home',
            'home'          => 'Home',
            'catalogo'      => 'Home',
            'login'         => 'Login',
            'logout'        => 'Logout',
            'dashboard'     => 'Dashboard',
            'pedidos'       => 'Pedido',
            'pedido'        => 'Pedido',
            'clientes'      => 'Cliente',
            'cliente'       => 'Cliente',
            'productos'     => 'Producto',
            'producto'      => 'Producto',
            'inventario'    => 'Inventario',
            'roles'         => 'Rol',
            'rol'           => 'Rol',
            'usuarios'      => 'Usuario',
            'usuario'       => 'Usuario',
            'reportes'      => 'Reporte',
            'reporte'       => 'Reporte',
        ];

        $clave = strtolower(trim($pagina));

        return $mapa[$clave] ?? ucfirst($clave);
    }

    /**
     * Busca y carga el archivo de controlador correspondiente.
     */
    private function despacharControlador(string $nombre): void
    {
        $archivo = $this->dir . $nombre . $this->sufijo;

        if (file_exists($archivo)) {
            require_once $archivo;
        } else {
            $this->mostrar404();
        }
    }

    /**
     * Responde con una página de error 404.
     */
    private function mostrar404(): void
    {
        http_response_code(404);
        $vista = BASE_PATH . '/resources/views/errors/404.php';
        if (file_exists($vista)) {
            require_once $vista;
        } else {
            echo '<h1>404 — Página no encontrada</h1>';
        }
        exit;
    }
}
