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
}