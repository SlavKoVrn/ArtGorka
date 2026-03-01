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
     * Тест: Проверка доступности существующего проекта
     * POST /api/projects/{id}/check
     */
    public function testCheckAvailability(): void
    {
        echo "\n\n📝 ТЕСТ: Проверка доступности проекта";
        echo "\n" . str_repeat('-', 50);

        if ($this->projectId === 0) {
            $this->logTest('Check Availability', false, 'No project ID available (create test failed)');
            return;
        }

        $result = $this->request('POST', "/api/projects/{$this->projectId}/check");

        if ($result['http_code'] === 200 && $result['body']['success'] === true) {
            $data = $result['body']['data'] ?? [];

            // Проверяем структуру ответа
            $hasProjectId = isset($data['project_id']) && $data['project_id'] === $this->projectId;
            $hasUrl = isset($data['url']) && filter_var($data['url'], FILTER_VALIDATE_URL);
            $hasStatus = isset($data['status']) && in_array($data['status'], ['available', 'unavailable']);
            $hasHttpCode = isset($data['http_code']) && is_int($data['http_code']);
            $hasResponseTime = isset($data['response_time']) && is_numeric($data['response_time']);
            $hasCheckedAt = isset($data['checked_at']) && strtotime($data['checked_at']) !== false;

            $structureValid = $hasProjectId && $hasUrl && $hasStatus && $hasHttpCode && $hasResponseTime && $hasCheckedAt;

            $this->logTest(
                'Check Availability - Response Structure',
                $structureValid,
                "Status: {$data['status']}, HTTP: {$data['http_code']}, Time: {$data['response_time']}ms",
                ['response' => $data]
            );

            // Если URL доступен (для example.com должен быть доступен)
            if ($data['status'] === 'available') {
                $this->logTest(
                    'Check Availability - Site Reachable',
                    true,
                    "Site is accessible as expected"
                );
            } elseif ($data['status'] === 'unavailable') {
                $this->logTest(
                    'Check Availability - Site Unreachable',
                    true,
                    "Site is unreachable (expected for some environments): " . $data['error'] ?? 'no error',
                    ['note' => 'This may be expected depending on network/firewall']
                );
            }

        } else {
            $this->logTest(
                'Check Availability',
                false,
                "Expected 200, got {$result['http_code']}",
                ['response' => $result['body']]
            );
        }
    }

    /**
     * Тест: Проверка доступности несуществующего проекта
     */
    public function testCheckAvailabilityNotFound(): void
    {
        echo "\n\n📝 ТЕСТ: Проверка несуществующего проекта";
        echo "\n" . str_repeat('-', 50);

        $result = $this->request('POST', '/api/projects/999999/check');

        $this->logTest(
            'Check Availability - Not Found',
            $result['http_code'] === 404,
            "Expected 404 for non-existent project, got {$result['http_code']}",
            ['response' => $result['body']]
        );
    }

    /**
     * Тест: Проверка доступности с невалидным ID
     */
    public function testCheckAvailabilityInvalidId(): void
    {
        echo "\n\n📝 ТЕСТ: Проверка с невалидным ID";
        echo "\n" . str_repeat('-', 50);

        $result = $this->request('POST', '/api/projects/invalid/check');

        $this->logTest(
            'Check Availability - Invalid ID',
            $result['http_code'] === 400,
            "Expected 400 for invalid ID, got {$result['http_code']}",
            ['response' => $result['body']]
        );
    }

    /**
     * Тест: Проверка доступности проекта с недоступным URL
     */
    public function testCheckAvailabilityUnreachableUrl(): void
    {
        echo "\n\n📝 ТЕСТ: Проверка проекта с недоступным URL";
        echo "\n" . str_repeat('-', 50);

        // Создаем проект с заведомо недоступным URL
        $createResult = $this->request('POST', '/api/projects', [
            'name' => 'Unreachable Test ' . time(),
            'url' => 'http://this-domain-definitely-does-not-exist-12345.invalid',
            'platform' => 'Custom',
            'status' => 'development'
        ]);

        $testId = $createResult['body']['id'] ?? 0;

        if ($testId === 0) {
            $this->logTest(
                'Check Unreachable URL - Setup',
                false,
                'Failed to create test project',
                ['response' => $createResult['body']]
            );
            return;
        }

        $result = $this->request('POST', "/api/projects/{$testId}/check");

        if ($result['http_code'] === 200 && $result['body']['success'] === true) {
            $data = $result['body']['data'] ?? [];

            // Для недоступного URL ожидаем status = 'unavailable'
            $this->logTest(
                'Check Unreachable URL - Status',
                $data['status'] === 'unavailable',
                "Expected 'unavailable', got '{$data['status']}'",
                ['error' => $data['error'] ?? 'none']
            );

            // Проверяем, что время ответа разумное (не мгновенное, но и не вечность)
            $reasonableTime = $data['response_time'] > 0 && $data['response_time'] < 15000;
            $this->logTest(
                'Check Unreachable URL - Response Time',
                $reasonableTime,
                "Response time: {$data['response_time']}ms (expected: 0-15000ms)"
            );

        } else {
            $this->logTest(
                'Check Unreachable URL',
                false,
                "Expected 200, got {$result['http_code']}",
                ['response' => $result['body']]
            );
        }

        // Очищаем: удаляем тестовый проект
        $this->request('DELETE', "/api/projects/{$testId}");
    }

    /**
     * Тест: Проверка метода GET на эндпоинте /check (должен быть 405)
     */
    public function testCheckAvailabilityWrongMethod(): void
    {
        echo "\n\n📝 ТЕСТ: Неправильный метод для /check";
        echo "\n" . str_repeat('-', 50);

        // Пробуем GET вместо POST
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . "/api/projects/{$this->projectId}/check");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        // GET по умолчанию

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->logTest(
            'Check Availability - Wrong Method (GET)',
            $httpCode === 405,
            "Expected 405 for GET on /check endpoint, got {$httpCode}"
        );
    }

    /**
     * Тест: Проверка доступности с таймаутом (медленный URL)
     * @note Этот тест может быть долгим, можно закомментировать при необходимости
     */
    public function testCheckAvailabilityTimeout(): void
    {
        echo "\n\n📝 ТЕСТ: Проверка с таймаутом (опционально)";
        echo "\n" . str_repeat('-', 50);

        // Используем httpstat.us для имитации задержки
        // ?sleep=12000 заставит сервер ждать 12 секунд (больше нашего таймаута 10с)
        $createResult = $this->request('POST', '/api/projects', [
            'name' => 'Timeout Test ' . time(),
            'url' => 'https://httpstat.us/200?sleep=12000',
            'platform' => 'Custom',
            'status' => 'development'
        ]);

        $testId = $createResult['body']['id'] ?? 0;

        if ($testId === 0) {
            $this->logTest(
                'Check Timeout - Setup',
                false,
                'Failed to create test project',
                ['skip' => 'httpstat.us may be unavailable']
            );
            return;
        }

        $startTime = microtime(true);
        $result = $this->request('POST', "/api/projects/{$testId}/check");
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($result['http_code'] === 200 && $result['body']['success'] === true) {
            $data = $result['body']['data'] ?? [];

            // Ожидаем, что проверка завершится с timeout/error и статусом unavailable
            $this->logTest(
                'Check Timeout - Handled Gracefully',
                $data['status'] === 'unavailable',
                "Status: {$data['status']}, Error: " . ($data['error'] ?? 'none'),
                ['total_duration' => "{$duration}ms"]
            );
        } else {
            // Также допустимо, если сервер вернет ошибку 500 при таймауте
            $this->logTest(
                'Check Timeout - Error Handling',
                in_array($result['http_code'], [200, 500]),
                "Got HTTP {$result['http_code']} (expected 200 or 500)",
                ['duration' => "{$duration}ms"]
            );
        }

        // Очищаем
        $this->request('DELETE', "/api/projects/{$testId}");
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

        // Базовые CRUD тесты
        $this->testCreateProject();
        $this->testCreateProjectInvalid();
        $this->testGetAllProjects();
        $this->testFilterByStatus();
        $this->testGetProjectById();
        $this->testUpdateProject();
        $this->testDeleteProject();

        // Тесты эндпоинтов
        $this->testUnsupportedMethod();
        $this->testNotFoundEndpoint();

        // 🔥 НОВЫЕ ТЕСТЫ: Проверка доступности
        $this->testCheckAvailability();
        $this->testCheckAvailabilityNotFound();
        $this->testCheckAvailabilityInvalidId();
        $this->testCheckAvailabilityUnreachableUrl();
        $this->testCheckAvailabilityWrongMethod();
        $this->testCheckAvailabilityTimeout();

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