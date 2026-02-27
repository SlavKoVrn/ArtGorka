<?php

namespace App\Logger;

class Logger
{
    private const LOG_DIR = __DIR__ . '/../../logs';
    private const LOG_FILE = 'app.log';
    private const MAX_FILE_SIZE = 10485760; // 10MB

    private const LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];

    private int $minLevel;

    public function __construct(int $minLevel = 2) // По умолчанию логируем WARNING и выше
    {
        $this->minLevel = $minLevel;
        $this->ensureLogDirectory();
    }

    /**
     * Создает директорию для логов если не существует
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir(self::LOG_DIR)) {
            mkdir(self::LOG_DIR, 0755, true);
        }

        // Защита от прямого доступа к логам
        $htaccessPath = self::LOG_DIR . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all");
        }
    }

    /**
     * Записывает сообщение в лог
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (self::LEVELS[$level] < self::LEVELS[$this->getLevelName($this->minLevel)]) {
            return;
        }

        $logEntry = $this->formatLogEntry($level, $message, $context);
        $this->writeToFile($logEntry);
    }

    /**
     * Логгирование ошибки
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Логгирование предупреждения
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Логгирование информационного сообщения
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Логгирование отладочной информации
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Логгирование критической ошибки
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    /**
     * Форматирование записи лога
     */
    private function formatLogEntry(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $microtime = microtime(true);
        
        // Добавляем информацию о вызове
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($backtrace[2]) ? 
            "{$backtrace[2]['file']}:{$backtrace[2]['line']}" : 
            'unknown';

        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';

        return "[{$timestamp}] [{$level}] [{$caller}] {$message}{$contextStr}" . PHP_EOL;
    }

    /**
     * Запись в файл с ротацией
     */
    private function writeToFile(string $entry): void
    {
        $filePath = self::LOG_DIR . '/' . self::LOG_FILE;

        // Ротация логов если файл слишком большой
        if (file_exists($filePath) && filesize($filePath) > self::MAX_FILE_SIZE) {
            $this->rotateLogs();
        }

        // Добавляем запись в лог
        file_put_contents($filePath, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Ротация лог-файлов
     */
    private function rotateLogs(): void
    {
        $filePath = self::LOG_DIR . '/' . self::LOG_FILE;
        $backupFile = self::LOG_DIR . '/' . self::LOG_FILE . '.' . date('Y-m-d-His');

        if (file_exists($filePath)) {
            rename($filePath, $backupFile);
        }

        // Удаляем старые логи (старше 30 дней)
        $this->cleanupOldLogs(30);
    }

    /**
     * Очистка старых лог-файлов
     */
    private function cleanupOldLogs(int $days): void
    {
        $files = glob(self::LOG_DIR . '/' . self::LOG_FILE . '.*');
        $cutoff = time() - ($days * 86400);

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }

    /**
     * Получение имени уровня логирования
     */
    private function getLevelName(int $level): string
    {
        return array_search($level, self::LEVELS) ?: 'INFO';
    }

    /**
     * Получение пути к лог-файлу (для тестов)
     */
    public static function getLogFilePath(): string
    {
        return self::LOG_DIR . '/' . self::LOG_FILE;
    }

    /**
     * Очистка всех логов (для тестов)
     */
    public static function clearLogs(): void
    {
        $filePath = self::LOG_DIR . '/' . self::LOG_FILE;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}