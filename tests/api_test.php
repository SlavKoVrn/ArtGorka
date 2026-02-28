<?php

/**
 * API Test Script for Project Management API
 * Использует php_curl для тестирования всех эндпоинтов
 */

class ApiTester
{
    private string $baseUrl;
    private array $testResults = [];
    private int $projectId = 0;

    public function __construct(string $baseUrl = 'http://localhost')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Выполняет HTTP запрос через cURL
     */
    private function request(string $method, string $endpoint, array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'http_code' => 0,
                'error' => $error,
                'body' => null
            ];
        }

        return [
            'success' => true,
            'http_code' => $httpCode,
            'body' => json_decode($response, true) ?? $response
        ];
    }

    /**
     * Записывает результат теста
     */
    private function logTest(string $testName, bool $passed, string $message = '', array $details = []): void
    {
        $this->testResults[] = [
            'name' => $testName,
            'passed' => $passed,
            'message' => $message,
            'details' => $details
        ];

        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo "\n{$status} | {$testName}";
        if ($message) {
            echo "\n       └─ {$message}";
        }
    }

    /**
     * Тест: Создание проекта
     */
    public function testCreateProject(): void
    {
        echo "\n\n📝 ТЕСТ: Создание проекта";
        echo "\n" . str_repeat('-', 50);

        $data = [
            'name' => 'Test Project ' . time(),
            'url' => 'https://example.com',
            'platform' => 'WordPress',
            'status' => 'development',
            'description' => 'Test project created via API test'
        ];

        $result = $this->request('POST', '/api/projects', $data);

        if ($result['http_code'] === 201 && $result['body']['success'] === true) {
            $this->projectId = $result['body']['id'] ?? 0;
            $this->logTest(
                'Create Project',
                true,
                "Project created with ID: {$this->projectId}",
                ['response' => $result['body']]
            );
        } else {
            $this->logTest(
                'Create Project',
                false,
                "Expected 201, got {$result['http_code']}",
                ['response' => $result['body']]
            );
        }
    }

    /**
     * Тест: Создание проекта с невалидными данными
     */
    public function testCreateProjectInvalid(): void
    {
        echo "\n\n📝 ТЕСТ: Валидация (невалидные данные)";
        echo "\n" . str_repeat('-', 50);

        // Тест 1: Пустое имя
        $result = $this->request('POST', '/api/projects', [
            'name' => '',
            'url' => 'https://example.com'
        ]);

        $this->logTest(
            'Validation - Empty Name',
            $result['http_code'] === 400,
            "Expected 400, got {$result['http_code']}"
        );

        // Тест 2: Невалидный URL
        $result = $this->request('POST', '/api/projects', [
            'name' => 'Test',
            'url' => 'not-a-valid-url'
        ]);

        $this->logTest(
            'Validation - Invalid URL',
            $result['http_code'] === 400,
            "Expected 400, got {$result['http_code']}"
        );

        // Тест 3: Невалидный статус
        $result = $this->request('POST', '/api/projects', [
            'name' => 'Test',
            'url' => 'https://example.com',
            'status' => 'invalid_status'
        ]);

        $this->logTest(
            'Validation - Invalid Status',
            $result['http_code'] === 400,
            "Expected 400, got {$result['http_code']}"
        );

        // Тест 4: Невалидная платформа
        $result = $this->request('POST', '/api/projects', [
            'name' => 'Test',
            'url' => 'https://example.com',
            'platform' => 'InvalidPlatform'
        ]);

        $this->logTest(
            'Validation - Invalid Platform',
            $result['http_code'] === 400,
            "Expected 400, got {$result['http_code']}"
        );
    }

    /**
     * Тест: Получение списка проектов
     */
    public function testGetAllProjects(): void
    {
        echo "\n\n📝 ТЕСТ: Получение списка проектов";
        echo "\n" . str_repeat('-', 50);

        $result = $this->request('GET', '/api/projects');

        if ($result['http_code'] === 200 && $result['body']['success'] === true) {
            $count = count($result['body']['data'] ?? []);
            $this->logTest(
                'Get All Projects',
                true,
                "Found {$count} project(s)",
                ['count' => $count]
            );
        } else {
            $this->logTest(
                'Get All Projects',
                false,
                "Expected 200, got {$result['http_code']}",
                ['response' => $result['body']]
            );
        }
    }

    /**
     * Тест: Фильтрация по статусу
     */
    public function testFilterByStatus(): void
    {
        echo "\n\n📝 ТЕСТ: Фильтрация по статусу";
        echo "\n" . str_repeat('-', 50);

        $result = $this->request('GET', '/api/projects?status=development');

        if ($result['http_code'] === 200) {
            $projects = $result['body']['data'] ?? [];
            $allMatch = true;
            foreach ($projects as $project) {
                if (($project['status'] ?? '') !== 'development') {
                    $allMatch = false;
                    break;
                }
            }

            $this->logTest(
                'Filter by Status (development)',
                $allMatch,
                "Found " . count($projects) . " project(s) with status 'development'",
                ['count' => count($projects)]
            );
        } else {
            $this->logTest(
                'Filter by Status',
                false,
                "Expected 200, got {$result['http_code']}"
            );
        }
    }

    /**
     * Тест: Получение проекта по ID
     */
    public function testGetProjectById(): void
    {
        echo "\n\n📝 ТЕСТ: Получение проекта по ID";
        echo "\n" . str_repeat('-', 50);

        if ($this->projectId === 0) {
            $this->logTest('Get Project by ID', false, 'No project ID available (create test failed)');
            return;
        }

        $result = $this->request('GET', "/api/projects/{$this->projectId}");

        if ($result['http_code'] === 200 && $result['body']['success'] === true) {
            $this->logTest(
                'Get Project by ID',
                true,
                "Project found: {$result['body']['data']['name']}",
                ['project' => $result['body']['data']]
            );
        } else {
            $this->logTest(
                'Get Project by ID',
                false,
                "Expected 200, got {$result['http_code']}",
                ['response' => $result['body']]
            );
        }

        // Тест: Несуществующий ID
        $result = $this->request('GET', '/api/projects/999999');
        $this->logTest(
            'Get Non-existent Project',
            $result['http_code'] === 404,
            "Expected 404, got {$result['http_code']}"
        );
    }

    /**
     * Тест: Обновление проекта
     */
    public function testUpdateProject(): void
    {
        echo "\n\n📝 ТЕСТ: Обновление проекта";
        echo "\n" . str_repeat('-', 50);

        if ($this->projectId === 0) {
            $this->logTest('Update Project', false, 'No project ID available (create test failed)');
            return;
        }

        $data = [
            'name' => 'Updated Project ' . time(),
            'status' => 'production',
            'platform' => 'Bitrix'
        ];

        $result = $this->request('PUT', "/api/projects/{$this->projectId}", $data);

        if ($result['http_code'] === 200 && $result['body']['success'] === true) {
            // Проверяем, что данные действительно обновились
            $getResult = $this->request('GET', "/api/projects/{$this->projectId}");
            $updated = $getResult['body']['data'] ?? [];

            $nameMatch = ($updated['name'] ?? '') === $data['name'];
            $statusMatch = ($updated['status'] ?? '') === $data['status'];
            $platformMatch = ($updated['platform'] ?? '') === $data['platform'];

            $this->logTest(
                'Update Project',
                $nameMatch && $statusMatch && $platformMatch,
                "Project updated successfully",
                ['updated_data' => $updated]
            );
        } else {
            $this->logTest(
                'Update Project',
                false,
                "Expected 200, got {$result['http_code']}",
                ['response' => $result['body']]
            );
        }

        // Тест: Обновление с невалидными данными
        $result = $this->request('PUT', "/api/projects/{$this->projectId}", [
            'status' => 'invalid_status'
        ]);

        $this->logTest(
            'Update - Invalid Status',
            $result['http_code'] === 400,
            "Expected 400, got {$result['http_code']}"
        );
    }

    /**
     * Тест: Удаление проекта
     */
    public function testDeleteProject(): void
    {
        echo "\n\n📝 ТЕСТ: Удаление проекта";
        echo "\n" . str_repeat('-', 50);

        // Сначала создадим новый проект для удаления
        $createResult = $this->request('POST', '/api/projects', [
            'name' => 'Project to Delete',
            'url' => 'https://delete-test.com',
            'platform' => 'Custom',
            'status' => 'development'
        ]);

        $deleteId = $createResult['body']['id'] ?? 0;

        if ($deleteId === 0) {
            $this->logTest('Delete Project', false, 'Failed to create project for deletion test');
            return;
        }

        // Удаляем проект
        $result = $this->request('DELETE', "/api/projects/{$deleteId}");

        if ($result['http_code'] === 200 && $result['body']['success'] === true) {
            $this->logTest(
                'Delete Project',
                true,
                "Project {$deleteId} deleted successfully"
            );

            // Проверяем, что проект действительно удален
            $getResult = $this->request('GET', "/api/projects/{$deleteId}");
            $this->logTest(
                'Verify Deletion',
                $getResult['http_code'] === 404,
                "Expected 404 after deletion, got {$getResult['http_code']}"
            );
        } else {
            $this->logTest(
                'Delete Project',
                false,
                "Expected 200, got {$result['http_code']}",
                ['response' => $result['body']]
            );
        }

        // Тест: Удаление несуществующего проекта
        $result = $this->request('DELETE', '/api/projects/999999');
        $this->logTest(
            'Delete Non-existent Project',
            $result['http_code'] === 404,
            "Expected 404, got {$result['http_code']}"
        );
    }

    /**
     * Тест: Неподдерживаемый метод
     */
    public function testUnsupportedMethod(): void
    {
        echo "\n\n📝 ТЕСТ: Неподдерживаемый метод";
        echo "\n" . str_repeat('-', 50);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/api/projects');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logTest(
            'Unsupported Method (PATCH)',
            $httpCode === 405,
            "Expected 405, got {$httpCode}"
        );
    }

    /**
     * Тест: Несуществующий эндпоинт
     */
    public function testNotFoundEndpoint(): void
    {
        echo "\n\n📝 ТЕСТ: Несуществующий эндпоинт";
        echo "\n" . str_repeat('-', 50);

        $result = $this->request('GET', '/api/nonexistent');

        $this->logTest(
            'Not Found Endpoint',
            $result['http_code'] === 404,
            "Expected 404, got {$result['http_code']}"
        );
    }

    /**
     * Вывод итогов тестирования
     */
    public function printSummary(): void
    {
        echo "\n\n" . str_repeat('=', 50);
        echo "\n📊 ИТОГИ ТЕСТИРОВАНИЯ";
        echo "\n" . str_repeat('=', 50);

        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, fn($t) => $t['passed']));
        $failed = $total - $passed;
        $percentage = $total > 0 ? round(($passed / $total) * 100, 2) : 0;

        echo "\n\nВсего тестов: {$total}";
        echo "\n✅ Пройдено: {$passed}";
        echo "\n❌ Провалено: {$failed}";
        echo "\n📈 Успешность: {$percentage}%";

        if ($failed > 0) {
            echo "\n\n⚠️  ПРОВАЛЕННЫЕ ТЕСТЫ:";
            echo "\n" . str_repeat('-', 50);
            foreach ($this->testResults as $test) {
                if (!$test['passed']) {
                    echo "\n❌ {$test['name']}";
                    echo "\n   └─ {$test['message']}";
                }
            }
        }

        echo "\n\n" . str_repeat('=', 50);
        echo $failed === 0 ? "\n🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ!" : "\n⚠️  ЕСТЬ ПРОБЛЕМЫ!";
        echo "\n" . str_repeat('=', 50) . "\n";
    }

    /**
     * Запуск всех тестов
     */
    public function runAll(): void
    {
        echo "\n🚀 ЗАПУСК ТЕСТИРОВАНИЯ API";
        echo "\nBase URL: {$this->baseUrl}";
        echo "\n" . str_repeat('=', 50);

        $this->testCreateProject();
        $this->testCreateProjectInvalid();
        $this->testGetAllProjects();
        $this->testFilterByStatus();
        $this->testGetProjectById();
        $this->testUpdateProject();
        $this->testDeleteProject();
        $this->testUnsupportedMethod();
        $this->testNotFoundEndpoint();

        $this->printSummary();
    }
}

// ============================================
// ЗАПУСК ТЕСТОВ
// ============================================

// Можно передать базовый URL через аргумент командной строки
$baseUrl = $argv[1] ?? 'http://artgorka';

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║     PROJECT API TEST SUITE             ║\n";
echo "╚════════════════════════════════════════╝\n";

$tester = new ApiTester($baseUrl);
$tester->runAll();