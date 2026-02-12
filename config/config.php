<?php

/**
 * Application configuration
 */
return [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'mvc_skeleton',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'debug' => $_ENV['APP_DEBUG'] ?? true,
        'name' => 'MVC Skeleton',
    ],
];
