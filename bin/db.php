<?php

/**
 * Database Console Command
 * Выполняет SQL команды через PDO
 * 
 * Использование:
 *   php bin/db.php migrate          - Выполнить миграции (schema.sql)
 *   php bin/db.php status           - Показать статус БД
 *   php bin/db.php query "SELECT..." - Выполнить SQL запрос
 *   php bin/db.php file <path.sql>  - Выполнить SQL файл
 *   php bin/db.php drop             - Удалить все таблицы
 *   php bin/db.php help             - Показать справку
 */

// Определяем базовую директорию проекта
define('BASE_DIR', dirname(__DIR__));

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = BASE_DIR . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// ============================================
// КЛАСС DATABASE COMMAND
// ============================================

class DatabaseCommand
{
    private PDO $pdo;
    private array $config;
    private string $logFile;

    public function __construct()
    {
        $this->config = require BASE_DIR . '/config/database.php';
        $this->logFile = BASE_DIR . '/logs/db_commands.log';
        $this->connect();
    }

    /**
     * Подключение к базе данных
     */
    private function connect(): void
    {
        $config = $this->config;
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            // Создаем базу данных если не существует
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET {$config['charset']} COLLATE {$config['charset']}_unicode_ci");
            
            // Выбираем базу данных
            $this->pdo->exec("USE `{$config['db_name']}`");
            
            $this->log('INFO', 'Database connection established');
            
        } catch (PDOException $e) {
            $this->output("❌ Database connection failed: {$e->getMessage()}", 'red');
            $this->log('ERROR', 'Connection failed: ' . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Выполнение миграций (schema.sql)
     */
    public function migrate(): void
    {
        $this->output("\n🚀 Запуск миграций...", 'cyan');
        
        $schemaFile = BASE_DIR . '/database/schema.sql';
        
        if (!file_exists($schemaFile)) {
            $this->output("❌ Файл schema.sql не найден: {$schemaFile}", 'red');
            exit(1);
        }

        $sql = file_get_contents($schemaFile);
        
        // Разделяем на отдельные запросы
        $statements = $this->parseSqlFile($sql);
        $total = count($statements);
        $executed = 0;
        $errors = 0;

        $this->output("📄 Найдено SQL запросов: {$total}", 'yellow');
        echo "\n";

        foreach ($statements as $i => $statement) {
            $statement = trim($statement);
            
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            try {
                $this->pdo->exec($statement);
                $executed++;
                $this->output("  ✅ [{$i}/{$total}] Выполнено", 'green');
                $this->log('INFO', "Statement {$i} executed successfully");
            } catch (PDOException $e) {
                $errors++;
                $this->output("  ❌ [{$i}/{$total}] Ошибка: {$e->getMessage()}", 'red');
                $this->log('ERROR', "Statement {$i} failed: " . $e->getMessage());
            }
        }

        echo "\n";
        $this->output("═══════════════════════════════════════", 'cyan');
        $this->output("📊 Результаты миграции:", 'cyan');
        $this->output("   Всего запросов: {$total}", 'white');
        $this->output("   Успешно: {$executed}", 'green');
        $this->output("   Ошибок: {$errors}", $errors > 0 ? 'red' : 'green');
        $this->output("═══════════════════════════════════════", 'cyan');

        exit($errors > 0 ? 1 : 0);
    }

    /**
     * Выполнение SQL запроса
     */
    public function query(string $sql): void
    {
        $this->output("\n🔍 Выполнение запроса...", 'cyan');
        $this->output("SQL: {$sql}", 'yellow');
        echo "\n";

        try {
            $startTime = microtime(true);
            
            // Определяем тип запроса
            $type = strtoupper(trim(substr($sql, 0, 6)));
            
            if (in_array($type, ['SELECT', 'SHOW', 'DESC', 'EXPLAI'])) {
                // SELECT запрос - выводим результаты
                $stmt = $this->pdo->query($sql);
                $results = $stmt->fetchAll();
                
                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);
                
                $this->output("⏱ Время выполнения: {$duration}ms", 'cyan');
                $this->output("📊 Найдено записей: " . count($results), 'green');
                echo "\n";
                
                if (empty($results)) {
                    $this->output("(пусто)", 'gray');
                } else {
                    $this->printTable($results);
                }
                
            } else {
                // INSERT, UPDATE, DELETE и т.д.
                $affected = $this->pdo->exec($sql);
                
                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);
                
                $this->output("⏱ Время выполнения: {$duration}ms", 'cyan');
                $this->output("✅ Затронуто строк: {$affected}", 'green');
                
                if ($type === 'INSERT') {
                    $this->output("🆔 Last Insert ID: " . $this->pdo->lastInsertId(), 'green');
                }
            }
            
            $this->log('INFO', "Query executed: " . substr($sql, 0, 100));
            
        } catch (PDOException $e) {
            $this->output("❌ Ошибка: {$e->getMessage()}", 'red');
            $this->log('ERROR', "Query failed: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Выполнение SQL файла
     */
    public function executeFile(string $filePath): void
    {
        $this->output("\n📄 Выполнение SQL файла...", 'cyan');
        
        if (!file_exists($filePath)) {
            // Пробуем относительно базы проекта
            $filePath = BASE_DIR . '/' . ltrim($filePath, '/');
        }
        
        if (!file_exists($filePath)) {
            $this->output("❌ Файл не найден: {$filePath}", 'red');
            exit(1);
        }

        $this->output("Файл: {$filePath}", 'yellow');
        
        $sql = file_get_contents($filePath);
        $statements = $this->parseSqlFile($sql);
        
        $total = count($statements);
        $executed = 0;
        $errors = 0;

        echo "\n";

        foreach ($statements as $i => $statement) {
            $statement = trim($statement);
            
            if (empty($statement)) {
                continue;
            }

            try {
                $this->pdo->exec($statement);
                $executed++;
                $this->output("  ✅ [{$i}/{$total}]", 'green');
            } catch (PDOException $e) {
                $errors++;
                $this->output("  ❌ [{$i}/{$total}] {$e->getMessage()}", 'red');
            }
        }

        echo "\n";
        $this->output("Готово: {$executed}/{$total} запросов", $errors > 0 ? 'yellow' : 'green');
        
        exit($errors > 0 ? 1 : 0);
    }

    /**
     * Показать статус базы данных
     */
    public function status(): void
    {
        $this->output("\n📊 Статус базы данных", 'cyan');
        echo "═══════════════════════════════════════\n\n";

        // Информация о сервере
        $version = $this->pdo->query("SELECT VERSION()")->fetchColumn();
        $this->output("🖥 MySQL Version: {$version}", 'white');

        // Информация о базе данных
        $dbName = $this->config['db_name'];
        $this->output("📁 Database: {$dbName}", 'white');

        // Таблицы
        $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $this->output("📋 Таблиц: " . count($tables), 'white');

        if (!empty($tables)) {
            echo "\n";
            $tableInfo = [];
            
            foreach ($tables as $table) {
                $count = $this->pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                $tableInfo[] = [
                    'Table' => $table,
                    'Rows' => $count
                ];
            }
            
            $this->printTable($tableInfo);
        }

        // Размер базы данных
        $size = $this->pdo->query("
            SELECT SUM(data_length + index_length) / 1024 / 1024 as size_mb
            FROM information_schema.tables
            WHERE table_schema = '{$dbName}'
        ")->fetchColumn();
        
        echo "\n";
        $this->output("💾 Размер БД: " . round($size, 2) . " MB", 'white');
        echo "═══════════════════════════════════════\n";
    }

    /**
     * Удалить все таблицы
     */
    public function drop(): void
    {
        $this->output("\n⚠️  ВНИМАНИЕ: Удаление всех таблиц!", 'red');
        $this->output("Это действие необратимо!", 'red');
        echo "\n";

        // Подтверждение
        echo "Вы уверены? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);

        if (trim($line) !== 'yes') {
            $this->output("❌ Отменено", 'yellow');
            exit(0);
        }

        $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            $this->output("ℹ️  Нет таблиц для удаления", 'yellow');
            exit(0);
        }

        $this->output("\n🗑 Удаление " . count($tables) . " таблиц...", 'cyan');

        // Отключаем foreign key checks
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $deleted = 0;
        $errors = 0;

        foreach ($tables as $table) {
            try {
                $this->pdo->exec("DROP TABLE `{$table}`");
                $deleted++;
                $this->output("  ✅ {$table}", 'green');
            } catch (PDOException $e) {
                $errors++;
                $this->output("  ❌ {$table}: {$e->getMessage()}", 'red');
            }
        }

        // Включаем foreign key checks
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        echo "\n";
        $this->output("Удалено таблиц: {$deleted}", $errors > 0 ? 'yellow' : 'green');
        
        $this->log('WARNING', "Dropped {$deleted} tables");
    }

    /**
     * Парсинг SQL файла на отдельные запросы
     */
    private function parseSqlFile(string $sql): array
    {
        // Удаляем комментарии
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Разделяем по точке с запятой
        $statements = explode(';', $sql);
        
        return array_filter(array_map('trim', $statements));
    }

    /**
     * Вывод таблицы в консоль
     */
    private function printTable(array $data): void
    {
        if (empty($data)) {
            return;
        }

        $columns = array_keys($data[0]);
        $colWidths = [];

        // Вычисляем ширину колонок
        foreach ($columns as $col) {
            $maxWidth = strlen($col);
            foreach ($data as $row) {
                $maxWidth = max($maxWidth, strlen((string) $row[$col]));
            }
            $colWidths[$col] = min($maxWidth, 50); // Максимум 50 символов
        }

        // Заголовок
        $header = '|';
        $separator = '+';
        
        foreach ($columns as $col) {
            $width = $colWidths[$col];
            $header .= ' ' . str_pad($col, $width) . ' |';
            $separator .= str_repeat('-', $width + 2) . '+';
        }

        echo $separator . "\n";
        echo $header . "\n";
        echo $separator . "\n";

        // Данные
        foreach ($data as $row) {
            $line = '|';
            foreach ($columns as $col) {
                $width = $colWidths[$col];
                $value = substr((string) $row[$col], 0, $width);
                $line .= ' ' . str_pad($value, $width) . ' |';
            }
            echo $line . "\n";
        }

        echo $separator . "\n";
    }

    /**
     * Вывод сообщения с цветом
     */
    private function output(string $message, string $color = 'white'): void
    {
        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'cyan' => "\033[36m",
            'white' => "\033[37m",
            'gray' => "\033[90m"
        ];

        $reset = "\033[0m";
        $colorCode = $colors[$color] ?? $colors['white'];

        echo $colorCode . $message . $reset . "\n";
    }

    /**
     * Логирование
     */
    private function log(string $level, string $message): void
    {
        $logDir = dirname($this->logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Показать справку
     */
    public function help(): void
    {
        echo <<<HELP

╔════════════════════════════════════════════════════════╗
║           DATABASE CONSOLE COMMAND                     ║
╚════════════════════════════════════════════════════════╝

Использование:
  php bin/db.php <command> [options]

Команды:
  migrate              Выполнить миграции (database/schema.sql)
  status               Показать статус базы данных
  query "SQL"          Выполнить SQL запрос
  file <path.sql>      Выполнить SQL файл
  drop                 Удалить все таблицы (требует подтверждения)
  help                 Показать эту справку

Примеры:
  php bin/db.php migrate
  php bin/db.php status
  php bin/db.php query "SELECT * FROM projects"
  php bin/db.php query "INSERT INTO projects (name, url) VALUES ('Test', 'https://test.com')"
  php bin/db.php file database/seed.sql
  php bin/db.php drop

═══════════════════════════════════════════════════════

HELP;
    }
}

// ============================================
// ОБРАБОТКА КОМАНДНОЙ СТРОКИ
// ============================================

if (php_sapi_name() !== 'cli') {
    echo "Этот скрипт можно запускать только из командной строки\n";
    exit(1);
}

$command = $argv[1] ?? 'help';
$param = $argv[2] ?? null;

$db = new DatabaseCommand();

switch ($command) {
    case 'migrate':
        $db->migrate();
        break;
    
    case 'status':
        $db->status();
        break;
    
    case 'query':
        if (!$param) {
            echo "❌ Укажите SQL запрос\n";
            echo "Пример: php bin/db.php query \"SELECT * FROM projects\"\n";
            exit(1);
        }
        $db->query($param);
        break;
    
    case 'file':
        if (!$param) {
            echo "❌ Укажите путь к SQL файлу\n";
            echo "Пример: php bin/db.php file database/schema.sql\n";
            exit(1);
        }
        $db->executeFile($param);
        break;
    
    case 'drop':
        $db->drop();
        break;
    
    case 'help':
    default:
        $db->help();
        break;
}