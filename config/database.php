<?php

/**
 * Database Configuration and Connection
 *
 * Provides PDO connection with error handling and proper configuration
 */

if (! function_exists('getDatabaseConnection')) {
    function getDatabaseConnection(): PDO
    {
        $config = [
            'host'    => getenv('DB_HOST') ?: 'localhost',
            'port'    => getenv('DB_PORT') ?: '3306',
            'dbname'  => getenv('DB_NAME') ?: 'marketplace',
            'user'    => getenv('DB_USER') ?: 'root',
            'pass'    => getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4',
        ];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
            return $pdo;
        } catch (PDOException $e) {
            // Log the error
            error_log('Database connection failed: ' . $e->getMessage());

            // Show user-friendly error in development
            if (getenv('APP_DEBUG') === 'true') {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }

            // Generic error in production
            throw new Exception('Unable to connect to database. Please try again later.');
        }
    }
}

return getDatabaseConnection();
