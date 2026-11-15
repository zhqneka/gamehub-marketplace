<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Controllers\OrderController;
use App\Controllers\ProductController;
use App\Http\Router;
use App\Controllers\AuthController;

return static function (Router $router): void {
    $router->get('/api/health', [HealthController::class, 'index']);
    $router->get('/api/products', [ProductController::class, 'index']);
    $router->get('/api/products/{id}', [ProductController::class, 'show']);
    $router->post('/api/orders', [OrderController::class, 'store']);
    $router->get('/api/orders', [OrderController::class, 'index']);
    $router->post('/api/auth/register', [AuthController::class, 'register']);
    $router->post('/api/auth/login', [AuthController::class, 'login']);
    $router->get('/api/auth/me', [AuthController::class, 'me']);
    $router->post('/api/auth/logout', [AuthController::class, 'logout']);
};