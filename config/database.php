<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'host' => Env::get('DB_HOST', ''),
    'port' => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', Env::get('DB_NAME', '')),
    'username' => Env::get('DB_USERNAME', Env::get('DB_USER', '')),
    'password' => Env::get('DB_PASSWORD', Env::get('DB_PASS', '')),
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
];
