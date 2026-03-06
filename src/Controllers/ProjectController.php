<?php

namespace App\Controllers;

use App\Models\Project;
use App\Validators\ProjectValidator;
use App\Logger\Logger;
use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        title: 'REST API',
        version: '1.0.0',
        description: 'REST API Documentation'
    )
)]
class ProjectController
{
    private Project $projectModel;
    private Logger $logger;

    public function __construct(Logger $logger = null)
    {
        $this->projectModel = new Project();
        $this->logger = $logger ?? new Logger();
    }

    private function jsonResponse($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * List all projects
     */
    #[OA\Get(
        path: '/api/projects',
        tags: ['Projects'],
        summary: 'Get all projects',
        description: 'Returns a list of all projects',
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Filter by status',
                schema: new OA\Schema(type: 'string', enum: ['development', 'production', 'maintenance', 'archived'])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Project Alpha'),
                                    new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://yandex.ru'),
                                    new OA\Property(property: 'platform', type: 'string', enum: ['WordPress', 'Bitrix', 'Custom', 'Other'], example: 'Bitrix'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['development', 'production', 'maintenance', 'archived'], example: 'production'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Project description'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01 00:00:00'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-02 00:00:00')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Failed to retrieve projects')
                    ]
                )
            )
        ]
    )]
    public function index(): void
    {
        try {
            $filters = $_GET;
            $this->logger->info('Getting all projects', ['filters' => $filters]);
            
            $projects = $this->projectModel->getAll($filters);
            
            $this->logger->info('Projects retrieved', ['count' => count($projects)]);
            $this->jsonResponse(['success' => true, 'data' => $projects]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get projects', [
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Failed to retrieve projects'], 500);
        }
    }

    /**
     * Get project by ID
     */
    #[OA\Get(
        path: '/api/projects/{id}',
        tags: ['Projects'],
        summary: 'Get project by ID',
        description: 'Returns a single project by its ID',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Project ID',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Project Alpha'),
                                    new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://yandex.ru'),
                                    new OA\Property(property: 'platform', type: 'string', enum: ['WordPress', 'Bitrix', 'Custom', 'Other'], example: 'Bitrix'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['development', 'production', 'maintenance', 'archived'], example: 'production'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Project description'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01 00:00:00'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-02 00:00:00')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid ID',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid ID')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Project not found')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Failed to retrieve project')
                    ]
                )
            )
        ]
    )]
    public function show(string $id): void
    {
        try {
            if (!is_numeric($id)) {
                $this->logger->warning('Invalid project ID', ['id' => $id]);
                $this->jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            }

            $this->logger->info('Getting project by ID', ['id' => $id]);
            $project = $this->projectModel->getById((int)$id);
            
            if (!$project) {
                $this->logger->warning('Project not found', ['id' => $id]);
                $this->jsonResponse(['success' => false, 'message' => 'Project not found'], 404);
            }

            $this->jsonResponse(['success' => true, 'data' => $project]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get project', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Failed to retrieve project'], 500);
        }
    }

    #[OA\Post(
        path: '/api/projects',
        tags: ['Projects'],
        summary: 'Create a new project',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['name', 'platform'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Project Alpha'),
                    new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://yandex.ru'),
                    new OA\Property(property: 'platform', type: 'string', enum: ['WordPress', 'Bitrix', 'Custom', 'Other'], example: 'Bitrix'),
                    new OA\Property(property: 'status', type: 'string', enum: ['development', 'production', 'maintenance', 'archived'], example: 'development'),
                    new OA\Property(property: 'description', type: 'string', example: 'Project description')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Project created'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Failed to create project')
                    ]
                )
            )
        ]
    )]
    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $this->logger->info('Creating new project', ['data' => array_keys($data)]);
            
            $errors = ProjectValidator::validateCreate($data);

            if (!empty($errors)) {
                $this->logger->warning('Project validation failed', ['errors' => $errors]);
                $this->jsonResponse(['success' => false, 'errors' => $errors], 400);
            }

            $newId = $this->projectModel->create($data);
            $this->logger->info('Project created', ['id' => $newId]);
            
            $this->jsonResponse(['success' => true, 'message' => 'Project created', 'id' => $newId], 201);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create project', [
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Failed to create project'], 500);
        }
    }

    public function update(string $id): void
    {
        try {
            if (!is_numeric($id)) {
                $this->logger->warning('Invalid project ID', ['id' => $id]);
                $this->jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            }

            $this->logger->info('Updating project', ['id' => $id]);

            if (!$this->projectModel->getById((int)$id)) {
                $this->logger->warning('Project not found for update', ['id' => $id]);
                $this->jsonResponse(['success' => false, 'message' => 'Project not found'], 404);
            }

            $data = $this->getJsonInput();
            $errors = ProjectValidator::validateUpdate($data);

            if (!empty($errors)) {
                $this->logger->warning('Project update validation failed', ['errors' => $errors]);
                $this->jsonResponse(['success' => false, 'errors' => $errors], 400);
            }

            $this->projectModel->update((int)$id, $data);
            $this->logger->info('Project updated', ['id' => $id]);
            
            $this->jsonResponse(['success' => true, 'message' => 'Project updated']);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update project', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update project'], 500);
        }
    }

    public function destroy(string $id): void
    {
        try {
            if (!is_numeric($id)) {
                $this->logger->warning('Invalid project ID', ['id' => $id]);
                $this->jsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
            }

            $this->logger->info('Deleting project', ['id' => $id]);

            $deleted = $this->projectModel->delete((int)$id);
            
            if (!$deleted) {
                $this->logger->warning('Project not found for deletion', ['id' => $id]);
                $this->jsonResponse(['success' => false, 'message' => 'Project not found or already deleted'], 404);
            }

            $this->logger->info('Project deleted', ['id' => $id]);
            $this->jsonResponse(['success' => true, 'message' => 'Project deleted']);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete project', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->jsonResponse(['success' => false, 'message' => 'Failed to delete project'], 500);
        }
    }

    /**
     * Проверка доступности проекта по ID
     * POST /api/projects/{id}/check
     */
    public function checkAvailability(int $projectId): void
    {
        try {
            $this->logger->info('Checking project availability', ['project_id' => $projectId]);

            // Получаем проект
            $project = $this->projectModel->getById($projectId);

            if (!$project) {
                $this->logger->warning('Project not found for availability check', ['id' => $projectId]);
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Project not found']);
                return;
            }

            $url = $project['url'];

            // Валидация URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $this->logger->warning('Invalid URL for availability check', [
                    'project_id' => $projectId,
                    'url' => $url
                ]);
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid project URL']);
                return;
            }

            // Выполняем проверку
            $result = $this->performUrlCheck($url);

            $this->logger->info('Availability check completed', [
                'project_id' => $projectId,
                'status' => $result['status'],
                'http_code' => $result['http_code'],
                'response_time' => $result['response_time']
            ]);

            // Возвращаем результат
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [
                    'project_id' => $projectId,
                    'url' => $url,
                    'status' => $result['status'],           // 'available' | 'unavailable'
                    'http_code' => $result['http_code'],     // 200, 404, 500, etc.
                    'response_time' => $result['response_time'], // в миллисекундах
                    'checked_at' => $result['checked_at']    // ISO 8601 timestamp
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to check availability', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to check availability',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Выполняет HTTP запрос для проверки доступности URL
     */
    private function performUrlCheck(string $url, int $timeout = 10): array
    {
        $startTime = microtime(true);
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'ProjectManager/1.0 Availability Checker',
            CURLOPT_NOBODY => true, // HEAD запрос для экономии трафика
            CURLOPT_ENCODING => ''
        ]);

        $result = [
            'http_code' => 0,
            'response_time' => 0,
            'status' => 'unavailable',
            'checked_at' => date('c'),
            'error' => null
        ];

        try {
            curl_exec($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $endTime = microtime(true);

            $result['http_code'] = $httpCode;
            $result['response_time'] = round(($endTime - $startTime) * 1000, 2); // мс
            $result['checked_at'] = date('c');

            // Считаем доступным если статус 2xx или 3xx
            if ($httpCode >= 200 && $httpCode < 400) {
                $result['status'] = 'available';
            }

            // Логируем ошибки cURL
            $curlError = curl_error($ch);
            if ($curlError) {
                $result['error'] = $curlError;
                $result['status'] = 'unavailable';
            }

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $result['status'] = 'unavailable';
        } finally {
            curl_close($ch);
        }

        return $result;
    }

}