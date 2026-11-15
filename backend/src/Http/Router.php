<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(
        string $method,
        string $path,
        callable|array $handler
    ): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->normalize($path),
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalize(
            parse_url($uri, PHP_URL_PATH) ?: '/'
        );

        $method = strtoupper($method);
        $allowed = [];

        foreach ($this->routes as $route) {
            $pattern = preg_replace(
                '/\{[a-zA-Z_][a-zA-Z0-9_]*\}/',
                '([^/]+)',
                $route['path']
            );

            if (!preg_match('#^' . $pattern . '$#', $path, $matches)) {
                continue;
            }

            $allowed[] = $route['method'];

            if ($route['method'] !== $method) {
                continue;
            }

            array_shift($matches);

            $payload = $this->invoke(
                $route['handler'],
                $matches
            );

            $status = isset($payload['_http_status'])
                ? (int) $payload['_http_status']
                : 200;

            unset($payload['_http_status']);

            Response::json($payload, $status);
            return;
        }

        if ($allowed !== []) {
            Response::json(
                ['error' => 'Method not allowed'],
                405,
                ['Allow' => implode(', ', $allowed)]
            );
            return;
        }

        Response::json(['error' => 'Not found'], 404);
    }

    private function invoke(
        callable|array $handler,
        array $params = []
    ): array {
        if (is_array($handler)) {
            $controller = new $handler[0]();
            $payload = $controller->{$handler[1]}(...$params);
        } else {
            $payload = $handler(...$params);
        }

        if (!is_array($payload)) {
            throw new \RuntimeException(
                'Route handlers must return an array.'
            );
        }

        return $payload;
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}