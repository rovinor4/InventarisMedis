<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function failIf(array $errors): void
    {
        $errors = array_filter($errors, static fn (mixed $error): bool => $error !== null && $error !== '');
        if ($errors !== []) {
            throw new HttpException('Validasi gagal', 422, $errors);
        }
    }

    public static function required(array $data, string $field, string $message): ?string
    {
        return !array_key_exists($field, $data) || trim((string) $data[$field]) === '' ? $message : null;
    }

    public static function email(array $data, string $field, string $message): ?string
    {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            return null;
        }

        return filter_var($data[$field], FILTER_VALIDATE_EMAIL) === false ? $message : null;
    }

    public static function integerMin(array $data, string $field, int $min, string $message): ?string
    {
        if (!isset($data[$field]) || filter_var($data[$field], FILTER_VALIDATE_INT) === false) {
            return $message;
        }

        return (int) $data[$field] < $min ? $message : null;
    }

    public static function in(array $data, string $field, array $allowed, string $message): ?string
    {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            return null;
        }

        return in_array($data[$field], $allowed, true) ? null : $message;
    }

    public static function date(array $data, string $field, string $message): ?string
    {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            return null;
        }

        $date = date_create((string) $data[$field]);

        return $date === false ? $message : null;
    }
}
