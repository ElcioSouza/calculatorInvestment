<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = Config::getString('DB_HOST', 'localhost');
            $port = Config::getString('DB_PORT', '3306');
            $name = Config::getString('DB_NAME', 'calculator_investment');
            $user = Config::getString('DB_USER', 'root');
            $pass = Config::getString('DB_PASS', '');
            $charset = Config::getString('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                throw new PDOException("Database connection failed: {$e->getMessage()}");
            }
        }

        return self::$instance;
    }

    public static function disconnect(): void
    {
        self::$instance = null;
    }
}
