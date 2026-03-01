<?php

namespace App\Controllers;

use App\Models\Project;
use App\Validators\ProjectValidator;
use App\Logger\Logger;

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
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
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