<?php
declare(strict_types=1);

/**
 * PDO database connection with safe defaults
 */

final class Database
{
    private static ?PDO $connection = null;

    /**
     * Get shared PDO connection instance
     */
    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        if (defined('DB_PORT')) {
            $dsn .= ';port=' . DB_PORT;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Avoid leaking credentials while still logging details in dev
            if (defined('APP_ENV') && APP_ENV === 'dev') {
                error_log('Database connection error: ' . $e->getMessage());
            }
            throw new RuntimeException('Impossible de se connecter à la base de données.');
        }

        return self::$connection;
    }
}


