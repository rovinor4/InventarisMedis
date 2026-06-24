<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

\App\Core\Env::load(dirname(__DIR__) . '/.env');

return [
    'database' => require dirname(__DIR__) . '/config/database.php',
    'app' => require dirname(__DIR__) . '/config/app.php',
];
