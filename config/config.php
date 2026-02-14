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
    'database_test' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3307',
        'database' => $_ENV['DB_DATABASE'] ?? 'thinkhuge_test',
        'username' => $_ENV['DB_USERNAME'] ?? 'thinkhuge',
        'password' => $_ENV['DB_PASSWORD'] ?? 'thinkhuge',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'App',
        'env' => $_ENV['APP_ENV'] ?? 'prod',
    ],
];
