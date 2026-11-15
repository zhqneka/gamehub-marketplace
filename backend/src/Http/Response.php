<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public static function json(array $payload, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
