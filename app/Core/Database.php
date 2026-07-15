<?php

namespace App\Core;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database
{
    /** @var array<string, PDO> Pool de conexiones Singleton */
    private static array $instances = [];

    /** @var PDO|null Referencia de la instancia actual en un modelo hijo */
    private ?PDO $pdo = null;

    public static function getConnection(string $type = 'main'): PDO
    {
        if (!isset(self::$instances[$type])) {
            try {
                $dotenv = Dotenv::createImmutable(BASE_PATH);
                $dotenv->safeLoad();

                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $port = $_ENV['DB_PORT'] ?? '3306';
                $user = $_ENV['DB_USER'] ?? 'root';
                $pass = $_ENV['DB_PASS'] ?? '';
                $name = $_ENV['DB_NAME'] ?? 'trigal_dorado';

                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // FALSE = sentencias preparadas resueltas por MySQL (elimina riesgo de SQL Injection)
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                self::$instances[$type] = new PDO($dsn, $user, $pass, $options);

            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode([
                    'resultado' => 500,
                    'mensaje'   => 'Error crítico de conexión a la base de datos.',
                    'debug'     => $e->getMessage(),
                ]));
            }
        }

        return self::$instances[$type];
    }

    public function LlamarConexion(string $type = 'main', ?PDO &$pdo = null): PDO
    {
        if ($pdo !== null) {
            $this->pdo = $pdo;
        }

        if ($this->pdo === null) {
            $this->pdo = self::getConnection($type);
        }

        return $this->pdo;
    }

    public function DestruirConexion(bool $force = true): void
    {
        if ($force) {
            $this->pdo = null;
        }
    }
}
