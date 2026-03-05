<?php
require 'vendor/autoload.php';

use OpenApi\Generator;

$openapi = Generator::scan(['src/']);

echo "Paths found: " . count($openapi->paths) . "\n";
echo "Components: " . count($openapi->components?->schemas ?? []) . "\n";
echo $openapi->toJson();