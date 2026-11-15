<?php

declare(strict_types=1);

/**
 * Database settings come only from the environment.
 * A missing DB_PASSWORD key is not replaced with a fallback password.
 */
return [
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_DATABASE'] ?? '',
    'username' => $_ENV['DB_USERNAME'] ?? '',
    'password' => array_key_exists('DB_PASSWORD', $_ENV) ? (string) $_ENV['DB_PASSWORD'] : null,
];
