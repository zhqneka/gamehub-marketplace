<?php

declare(strict_types=1);

namespace App\Http;

final class Cors
{
    /**
     * @param list<string> $allowedOrigins
     * @param list<string> $allowedMethods
     * @param list<string> $allowedHeaders
     */
    private function __construct(
        private readonly array $allowedOrigins,
        private readonly array $allowedMethods,
        private readonly array $allowedHeaders,
    ) {
    }

    public static function fromConfig(string $configPath): self
    {
        /** @var array{allowed_origins: list<string>, allowed_methods: list<string>, allowed_headers: list<string>} $config */
        $config = require $configPath;

        return new self(
            $config['allowed_origins'],
            $config['allowed_methods'],
            $config['allowed_headers'],
        );
    }

    public function apply(?string $origin): void
    {
        if ($origin === null || $origin === '') {
            return;
        }

        $normalized = rtrim($origin, '/');

        if (!in_array($normalized, $this->allowedOrigins, true)) {
            return;
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));
        header('Access-Control-Max-Age: 86400');
    }
}
