<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(
        string $message,
        private int $status = 400,
        private array $errors = []
    ) {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
