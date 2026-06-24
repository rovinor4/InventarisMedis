<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name' => Env::get('APP_NAME', 'MedicalInventoryAPI'),
    'env' => Env::get('APP_ENV', 'local'),
    'debug' => filter_var(Env::get('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => Env::get('APP_URL', 'http://localhost:8000'),
    'token_ttl_hours' => (int) Env::get('TOKEN_TTL_HOURS', 24),
    'log_path' => dirname(__DIR__) . '/storage/logs/app.log',
];
