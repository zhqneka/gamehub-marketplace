<?php

declare(strict_types=1);

namespace App;

use App\Http\Cors;
use App\Http\Response;
use App\Http\Router;
use Dotenv\Dotenv;
use Throwable;

final class Application
{
    private function __construct(
        private readonly string $basePath,
    ) {
    }

    public static function boot(string $basePath): self
    {
        $dotenv = Dotenv::createImmutable($basePath);
        $dotenv->safeLoad();

        return new self($basePath);
    }

    public function handle(): void
    {
        try {
            $cors = Cors::fromConfig($this->basePath . '/config/cors.php');
            $cors->apply($_SERVER['HTTP_ORIGIN'] ?? null);

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
                http_response_code(204);
                return;
            }

            $router = new Router();
            $register = require $this->basePath . '/routes/api.php';
            $register($router);
            $router->dispatch(
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REQUEST_URI'] ?? '/'
            );
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            Response::json(['error' => 'Internal server error'], 500);
        }
    }
}
