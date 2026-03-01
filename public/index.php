<?php

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';

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
// НАСТРОЙКА ЛОГГЕРА
// ============================================

use App\Logger\Logger;
use App\Helpers\UrlHelper;

// Создаем глобальный экземпляр логгера
$logger = new Logger(0); // 2 = WARNING и выше

// Перехват всех PHP ошибок
set_error_handler(function($severity, $message, $file, $line) use ($logger) {
    $logger->error("PHP Error: {$message}", [
        'severity' => $severity,
        'file' => $file,
        'line' => $line
    ]);
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Перехват необработанных исключений
set_exception_handler(function($exception) use ($logger) {
    $logger->critical("Uncaught Exception: {$exception->getMessage()}", [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error',
        // В продакшене не показываем детали ошибки пользователю
        // 'debug' => $exception->getMessage()
    ]);
});

// Логгирование начала запроса
$logger->info('Request started', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// ============================================
// ОБРАБОТКА ЗАПРОСА
// ============================================

try {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];

    if (strpos($uri, '/api/projects') === 0) {
        $controller = new \App\Controllers\ProjectController($logger);
        
        $pathInfo = trim(substr($uri, strlen('/api/projects')), '/');

        // Парсим путь на части: [id, action]
        $pathParts = explode('/', $pathInfo);
        $projectId = $pathParts[0] ?? null;
        $action = $pathParts[1] ?? null;

        if ($method === 'GET') {
            if (empty($pathInfo)) {
                $controller->index();
            } else {
                $controller->show((int)$projectId);
            }
        } elseif ($method === 'POST') {
            if ($action === 'check' && is_numeric($projectId)) {
                // ========================================
                // НОВЫЙ ЭНДПОИНТ: POST /api/projects/{id}/check
                // ========================================
                $controller->checkAvailability((int)$projectId);
            }else{
                $controller->store();
            }
        } elseif ($method === 'PUT') {
            $controller->update((int)$projectId);
        } elseif ($method === 'DELETE') {
            $controller->destroy((int)$projectId);
        } else {
            $logger->warning('Method not allowed', ['method' => $method]);
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        }
    } else if ($uri === '/') {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        $templateData = [
            'api_endpoint' => UrlHelper::getBaseUrl() . '/api/projects'
        ];
        // Рендерим шаблон с заменой плейсхолдеров
        echo UrlHelper::render(__DIR__ . '/../views/home.html', $templateData);
        $logger->info('Root page served');

    } else {
        $logger->warning('Endpoint not found', ['uri' => $uri]);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }

    $logger->info('Request completed', [
        'status' => http_response_code()
    ]);

} catch (Exception $e) {
    $logger->critical('Request failed', [
        'exception' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error'
    ]);
}