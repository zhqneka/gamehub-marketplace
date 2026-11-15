<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Database\Connection;
final class HealthController
{
    /**
     * @return array{status: string, database: string}
     */
    public function index(): array
    {
        return [
            'status' => 'ok',
            'database' => Connection::status(),
        ];
    }
}
