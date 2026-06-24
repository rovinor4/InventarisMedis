<?php

function json_response(mixed $data, int $status = 200): void
{
    http_response_code($status);
    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function read_json_body(): array
{
    $input = file_get_contents('php://input');
    if ($input === false || trim($input) === '') {
        return [];
    }

    $decoded = json_decode($input, true);
    if (!is_array($decoded)) {
        throw new InvalidArgumentException('Body JSON tidak valid.');
    }

    return $decoded;
}

function require_fields(array $data, array $fields): void
{
    $missing = [];
    foreach ($fields as $field) {
        if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
            $missing[] = $field;
        }
    }

    if ($missing !== []) {
        throw new InvalidArgumentException('Field wajib belum lengkap: ' . implode(', ', $missing));
    }
}

function int_or_null(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    return (int) $value;
}
