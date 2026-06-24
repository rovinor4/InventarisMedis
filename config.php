<?php

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'inventaris_medis',
        'user' => getenv('DB_USER') ?: 'username_db',
        'pass' => getenv('DB_PASS') ?: 'password_db',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
];
