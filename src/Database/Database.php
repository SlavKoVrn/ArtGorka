<?php

namespace App\Database;

use PDO;
use PDOException;
use App\Logger\Logger;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private Logger $logger;

    private function __construct()
    {
        $this->logger = new Logger();
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db_name']};charset={$config['charset']}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);
            $this->logger->info('Database connection established');
        } catch (PDOException $e) {
            $this->logger->critical('Database connection failed', [
                'error' => $e->getMessage(),
                'dsn' => $dsn // Внимание: не логгируйте пароли в продакшене!
            ]);
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function __destruct()
    {
        $this->connection = null;
        $this->logger->debug('Database connection closed');
    }
}