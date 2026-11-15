<?php

declare(strict_types=1);

$raw = $_ENV['CORS_ALLOWED_ORIGINS']
    ?? getenv('CORS_ALLOWED_ORIGINS')
    ?: 'http://localhost:5173,http://127.0.0.1:5173';

$origins = array_values(array_filter(array_map(
    static fn (string $origin): string => rtrim(trim($origin), '/'),
    explode(',', $raw)
)));

return [
    'allowed_origins' => $origins,
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type'],
];
