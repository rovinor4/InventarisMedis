<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private ?array $json = null;

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return '/' . trim($path, '/');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        $input = file_get_contents('php://input');
        if ($input === false || trim($input) === '') {
            return $this->json = [];
        }

        $decoded = json_decode($input, true);
        if (!is_array($decoded)) {
            throw new HttpException('Validasi gagal', 422, ['body' => 'Body JSON tidak valid']);
        }

        return $this->json = $decoded;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');
        if ($header === null || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    public function header(string $name): ?string
    {
        if (strtolower($name) === 'content-type' && isset($_SERVER['CONTENT_TYPE'])) {
            return (string) $_SERVER['CONTENT_TYPE'];
        }

        if (strtolower($name) === 'content-length' && isset($_SERVER['CONTENT_LENGTH'])) {
            return (string) $_SERVER['CONTENT_LENGTH'];
        }

        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$key])) {
            return (string) $_SERVER[$key];
        }

        if (strtolower($name) === 'authorization' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return null;
    }
}
