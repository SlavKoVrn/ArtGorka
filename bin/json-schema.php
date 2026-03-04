<?php
// bin/json-schema.php - Minimal @OA annotation extractor (fallback)

$source = file_get_contents(__DIR__ . '/../public/index.php');

// Very basic extraction - for production, use swagger-php library!
preg_match_all('/@OA\\\\(Get|Post|Put|Delete|Patch)\([^)]*path="([^"]+)"[^)]*\)/', $source, $ops);

$paths = [];
foreach ($ops[2] as $i => $path) {
    $method = strtolower($ops[1][$i]);
    $paths[$path][$method] = [
        'tags' => ['Projects'],
        'summary' => 'Auto-generated endpoint',
        'responses' => [
            '200' => ['description' => 'Success'],
            '400' => ['description' => 'Bad request'],
            '404' => ['description' => 'Not found']
        ]
    ];
}

$schema = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'REST API',
        'version' => 'srv 1.0',
        'description' => 'API for managing projects'
    ],
    'paths' => $paths,
    'components' => [
        'schemas' => [
            'Project' => ['type' => 'object'],
            'ProjectInput' => ['type' => 'object'],
            'CheckResult' => ['type' => 'object']
        ]
    ]
];

file_put_contents(__DIR__ . '/../public/swagger.json', json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✓ Generated public/swagger.json (basic)\n";