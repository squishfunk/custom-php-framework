<?php

/**
 * Application configuration
 */
return [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'thinkhuge',
        'username' => $_ENV['DB_USERNAME'] ?? 'thinkhuge',
        'password' => $_ENV['DB_PASSWORD'] ?? 'thinkhuge',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'debug' => $_ENV['APP_DEBUG'] ?? true,
        'name' => 'MVC Skeleton',
    ],
];
