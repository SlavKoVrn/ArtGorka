<?php
/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="REST API",
 *         version="srv 1.0",
 *         description="API for managing projects"
 *     ),
 *     servers={@OA\Server(url="http://localhost", description="Local server")},
 *     tags={@OA\Tag(name="Projects", description="Project management endpoints")}
 * )
 */

/**
 * @OA\Schema(
 *     schema="ProjectInput",
 *     type="object",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", example="Project name", minLength=1, maxLength=255),
 *     @OA\Property(property="url", type="string", format="uri", example="https://yandex.ru"),
 *     @OA\Property(property="platform", type="string", example="Bitrix"),
 *     @OA\Property(property="status", type="string", example="production", enum={"draft", "production", "archived"}),
 *     @OA\Property(property="description", type="string", example="project description", maxLength=1000)
 * )
 *
 * @OA\Schema(
 *     schema="Project",
 *     type="object",
 *     allOf={
 *         @OA\Schema(
 *             @OA\Property(property="id", type="integer", example=1, readOnly=true),
 *             @OA\Property(property="createdAt", type="string", format="date-time", example="2024-01-15T10:30:00Z", readOnly=true),
 *             @OA\Property(property="updatedAt", type="string", format="date-time", example="2024-01-20T14:45:00Z", readOnly=true)
 *         ),
 *         @OA\Schema(ref="#/components/schemas/ProjectInput")
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="CheckResult",
 *     type="object",
 *     @OA\Property(property="projectId", type="integer", example=1),
 *     @OA\Property(property="checkedAt", type="string", format="date-time", example="2024-06-01T12:00:00Z"),
 *     @OA\Property(property="overallStatus", type="string", enum={"passed", "warning", "failed"}, example="passed"),
 *     @OA\Property(
 *         property="checks",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="name", type="string", example="ssl"),
 *             @OA\Property(property="status", type="string", enum={"passed", "warning", "failed", "skipped"}, example="passed"),
 *             @OA\Property(property="message", type="string", example="SSL certificate valid"),
 *             @OA\Property(property="details", type="object")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/api/projects",
 *     tags={"Projects"},
 *     summary="Get all projects",
 *     description="Retrieve a list of projects, optionally filtered by status",
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter projects by status",
 *         required=false,
 *         @OA\Schema(type="string", enum={"draft", "production", "archived"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of projects",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Project"))
 *     ),
 *     @OA\Response(response=400, description="Bad request")
 * )
 *
 * @OA\Post(
 *     path="/api/projects",
 *     tags={"Projects"},
 *     summary="Create a new project",
 *     description="Create a new project with the provided data",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/ProjectInput")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Project created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Project")
 *     ),
 *     @OA\Response(response=400, description="Bad request - invalid input")
 * )
 */

/**
 * @OA\Get(
 *     path="/api/projects/{id}",
 *     tags={"Projects"},
 *     summary="Get project by ID",
 *     description="Retrieve a single project by its ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Project ID",
 *         required=true,
 *         @OA\Schema(type="integer", minimum=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Project details",
 *         @OA\JsonContent(ref="#/components/schemas/Project")
 *     ),
 *     @OA\Response(response=404, description="Project not found")
 * )
 *
 * @OA\Put(
 *     path="/api/projects/{id}",
 *     tags={"Projects"},
 *     summary="Update project",
 *     description="Update an existing project by ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Project ID",
 *         required=true,
 *         @OA\Schema(type="integer", minimum=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/ProjectInput")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Project updated successfully",
 *         @OA\JsonContent(ref="#/components/schemas/Project")
 *     ),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=404, description="Project not found")
 * )
 *
 * @OA\Delete(
 *     path="/api/projects/{id}",
 *     tags={"Projects"},
 *     summary="Delete project",
 *     description="Delete a project by ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Project ID",
 *         required=true,
 *         @OA\Schema(type="integer", minimum=1)
 *     ),
 *     @OA\Response(response=204, description="Project deleted successfully"),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=404, description="Project not found")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/projects/{id}/check",
 *     tags={"Projects"},
 *     summary="Check project health or validity",
 *     description="Perform validation or health checks on an existing project by ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="Project ID",
 *         required=true,
 *         @OA\Schema(type="integer", minimum=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Check completed successfully",
 *         @OA\JsonContent(ref="#/components/schemas/CheckResult")
 *     ),
 *     @OA\Response(response=400, description="Bad request"),
 *     @OA\Response(response=404, description="Project not found")
 * )
 */

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

        if ($action === 'check' and $method !== 'POST'){
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Check Availability - Wrong Method']);
        } elseif ($method === 'GET') {
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

    } else if ($uri === '/docs') {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>API Docs</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
        </head>
        <body>
            <div id="swagger-ui"></div>
            <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
            <script>
                SwaggerUIBundle({
                    url: "/swagger.json",
                    dom_id: "#swagger-ui"
                });
            </script>
        </body>
        </html>';
        exit;
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