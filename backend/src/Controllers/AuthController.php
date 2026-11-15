<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Database\Connection;
use PDO;
use Throwable;
final class AuthController
{
    public function register(): array
    {
        $data = json_decode(file_get_contents('php://input') ?: '', true);

        if (!is_array($data)) {
            return [
                '_http_status' => 400,
                'error' => 'Invalid JSON request.',
            ];
        }

        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if ($name === '' || strlen($name) > 100) {
            return [
                '_http_status' => 422,
                'error' => 'Please enter a valid name.',
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                '_http_status' => 422,
                'error' => 'Please enter a valid email address.',
            ];
        }

        if (strlen($password) < 8) {
            return [
                '_http_status' => 422,
                'error' => 'Password must contain at least 8 characters.',
            ];
        }

        try {
            $pdo = Connection::getConnection();

            $check = $pdo->prepare(
                'SELECT id FROM users WHERE email = :email'
            );
            $check->execute(['email' => $email]);

            if ($check->fetch()) {
                return [
                    '_http_status' => 409,
                    'error' => 'An account with this email already exists.',
                ];
            }

            $pdo->beginTransaction();

            $statement = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash)
                 VALUES (:name, :email, :password_hash)
                 RETURNING id, name, email'
            );

            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
            ]);

            $user = $statement->fetch(PDO::FETCH_ASSOC);
            $token = $this->createToken($pdo, (int) $user['id']);

            $pdo->commit();

            return [
                '_http_status' => 201,
                'success' => true,
                'message' => 'Account created successfully.',
                'token' => $token,
                'user' => $user,
            ];
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Registration failed: ' . $exception->getMessage());

            return [
                '_http_status' => 500,
                'error' => 'Could not create your account.',
            ];
        }
    }

    public function login(): array
    {
        $data = json_decode(file_get_contents('php://input') ?: '', true);

        if (!is_array($data)) {
            return [
                '_http_status' => 400,
                'error' => 'Invalid JSON request.',
            ];
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $password === ''
        ) {
            return [
                '_http_status' => 422,
                'error' => 'Email and password are required.',
            ];
        }

        try {
            $pdo = Connection::getConnection();

            $statement = $pdo->prepare(
                'SELECT id, name, email, password_hash
                 FROM users
                 WHERE email = :email'
            );

            $statement->execute(['email' => $email]);
            $user = $statement->fetch(PDO::FETCH_ASSOC);

            if (
                !$user ||
                !password_verify($password, $user['password_hash'])
            ) {
                return [
                    '_http_status' => 401,
                    'error' => 'Incorrect email or password.',
                ];
            }

            $token = $this->createToken($pdo, (int) $user['id']);

            unset($user['password_hash']);

            return [
                'success' => true,
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user,
            ];
        } catch (Throwable $exception) {
            error_log('Login failed: ' . $exception->getMessage());

            return [
                '_http_status' => 500,
                'error' => 'Could not log in.',
            ];
        }
    }

    public function me(): array
    {
        $user = $this->authenticatedUser();

        if (!$user) {
            return [
                '_http_status' => 401,
                'error' => 'Authentication required.',
            ];
        }

        return [
            'success' => true,
            'user' => $user,
        ];
    }

    public function logout(): array
    {
        $token = $this->bearerToken();

        if (!$token) {
            return [
                '_http_status' => 401,
                'error' => 'Authentication required.',
            ];
        }

        $pdo = Connection::getConnection();

        $statement = $pdo->prepare(
            'DELETE FROM auth_tokens WHERE token_hash = :token_hash'
        );

        $statement->execute([
            'token_hash' => hash('sha256', $token),
        ]);

        return [
            'success' => true,
            'message' => 'Logged out successfully.',
        ];
    }

    public function authenticatedUser(): ?array
    {
        $token = $this->bearerToken();

        if (!$token) {
            return null;
        }

        $pdo = Connection::getConnection();

        $statement = $pdo->prepare(
            'SELECT users.id, users.name, users.email
             FROM auth_tokens
             JOIN users ON users.id = auth_tokens.user_id
             WHERE auth_tokens.token_hash = :token_hash
               AND auth_tokens.expires_at > CURRENT_TIMESTAMP'
        );

        $statement->execute([
            'token_hash' => hash('sha256', $token),
        ]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function createToken(PDO $pdo, int $userId): string
    {
        $token = bin2hex(random_bytes(32));

        $statement = $pdo->prepare(
            'INSERT INTO auth_tokens (user_id, token_hash, expires_at)
             VALUES (
                :user_id,
                :token_hash,
                CURRENT_TIMESTAMP + INTERVAL \'30 days\'
             )'
        );

        $statement->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
        ]);

        return $token;
    }

    
    private function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (!$header && function_exists('getallheaders')) {
            $headers = getallheaders();

            $header = $headers['Authorization']
                ?? $headers['authorization']
                ?? '';
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
            return null;
        }

        return trim($matches[1]);
    }
}