<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Config;
use PDO;
use PDOException;

/**
 * Database connection manager using Singleton pattern
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Get database connection instance
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(bool $testing = false): PDO
    {
        if (self::$instance === null) {
            $dbConfig = $testing ? Config::get('database_test') : Config::get('database');

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['database'],
                $dbConfig['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $dbConfig['username'],
                    $dbConfig['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new PDOException('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }
    public static function rollBack(): bool
    {
        return self::getConnection()->rollBack();
    }

    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }

    private function __clone()
    {
    }
}
