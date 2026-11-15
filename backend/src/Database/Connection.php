<?php
namespace App\Database;
use PDO;
use Throwable;

class Connection
{
    public static function getConnection(): PDO
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        if (
            empty($config['database']) ||
            empty($config['username']) ||
            $config['password'] === null
        ) {
            throw new \RuntimeException('Database is not configured.');
        }

        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

        if (!empty($config['sslmode'])) {
            $dsn .= ";sslmode={$config['sslmode']}";
        }

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function status(): string
    {
        try {
            $pdo = self::getConnection();
            $pdo->query('SELECT 1');

            return 'connected';
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            return 'unavailable';
        }
    }
}
