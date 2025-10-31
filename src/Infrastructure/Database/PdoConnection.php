<?php
namespace App\Infrastructure\Database;

use PDO;
use PDOException;
use RuntimeException;

class PdoConnection
{
    private static ?PDO $pdo = null;

    public static function getInstance(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $db   = defined('DB_NAME') ? DB_NAME : 'app_auth';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

        try {
            $dsnNoDb = sprintf('mysql:host=%s;charset=%s', $host, $charset);
            $pdoNoDb = new PDO($dsnNoDb, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdoNoDb->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` DEFAULT CHARACTER SET %s COLLATE utf8mb4_unicode_ci',
                $db,
                $charset
            ));
        } catch (PDOException $e) {
            throw new RuntimeException('No fue posible preparar la base de datos: ' . $e->getMessage(), 0, $e);
        }

        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $db, $charset);
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('No fue posible conectar a la base de datos: ' . $e->getMessage(), 0, $e);
        }

        return self::$pdo;
    }
}
