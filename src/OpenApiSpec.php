<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        title: 'Project API',
        version: '1.0.0',
        description: 'API Documentation'
    ),
    servers: [
        new OA\Server(url: 'http://localhost:8000', description: 'Local')
    ],
    components: new OA\Components(
        securitySchemes: [
            new OA\SecurityScheme(
                securityScheme: 'bearerAuth',
                type: 'http',
                scheme: 'bearer',
                bearerFormat: 'JWT'
            )
        ]
    )
)]
class OpenApiSpec
{
    // This class is just a container for the OpenApi annotation
}