<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public function __construct(private string $path)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        unset($context['password'], $context['token'], $context['password_hash']);

        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context === [] ? '' : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }
}
