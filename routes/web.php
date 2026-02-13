<?php

use App\Core\Router;
use App\Controller\ClientController;
use App\Controller\AuthController;
use App\Controller\TransactionController;

/** @var Router $router */

// Auth Routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);

// Protected Routes
$router->get('/logout', [AuthController::class, 'logout'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

// Client Routes
$router->get('/', [ClientController::class, 'index'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->get('/client/create', [ClientController::class, 'create'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->post('/client', [ClientController::class, 'store'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->get('/client/{id}', [ClientController::class, 'show'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->post('/client/{id}', [ClientController::class, 'update'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->post('/client/{id}/delete', [ClientController::class, 'delete'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

// Transaction Routes
$router->get('/transaction', [TransactionController::class, 'create'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->post('/transaction', [TransactionController::class, 'store'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());