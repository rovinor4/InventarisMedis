<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function success(string $message, mixed $data = null, int $status = 200): void
    {
        self::json([
            'message' => $message,
            'data' => $data ?? (object) [],
        ], $status);
    }

    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        $payload = ['message' => $message];
        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        self::json($payload, $status);
    }

    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        if (PHP_SAPI !== 'cli') {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
