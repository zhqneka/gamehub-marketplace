<?php

declare(strict_types=1);

$databaseUrl = $_ENV['DATABASE_URL'] ?? (getenv('DATABASE_URL') ?: '');
if ($databaseUrl !== '') {
    $parts = parse_url($databaseUrl);

    if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
        throw new RuntimeException('Invalid DATABASE_URL configuration.');
    }

    return [
        'host' => $parts['host'],
        'port' => $parts['port'] ?? '5432',
        'database' => ltrim($parts['path'], '/'),
        'username' => urldecode($parts['user'] ?? ''),
        'password' => urldecode($parts['pass'] ?? ''),
        'sslmode' => 'require',
    ];
}

return [
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_DATABASE'] ?? '',
    'username' => $_ENV['DB_USERNAME'] ?? '',
    'password' => array_key_exists('DB_PASSWORD', $_ENV)
        ? (string) $_ENV['DB_PASSWORD']
        : null,
    'sslmode' => $_ENV['DB_SSLMODE'] ?? null,
];
