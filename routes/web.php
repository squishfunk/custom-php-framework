<?php

use App\Core\Router;
use App\Middleware\SessionMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Controller\ClientController;
use App\Controller\AuthController;
use App\Controller\TransactionController;
use App\Controller\StatisticController;

/** @var Router $router */

$router->use(new SessionMiddleware());
$router->use(new CsrfMiddleware());

// Auth
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);

$router->get('/logout', [AuthController::class, 'logout'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->get('/', [ClientController::class, 'index'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

// Statistics
$router->get('/statistics', [StatisticController::class, 'index'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

// Client
$router->get('/clients', [ClientController::class, 'index'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->get('/clients/create', [ClientController::class, 'create'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->post('/clients', [ClientController::class, 'store'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->get('/clients/{id}', [ClientController::class, 'show'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->get('/clients/{id}/edit', [ClientController::class, 'edit'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->post('/clients/{id}', [ClientController::class, 'update'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->post('/clients/{id}/delete', [ClientController::class, 'delete'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

// Transaction
$router->get('/transactions/create', [TransactionController::class, 'create'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());

$router->post('/transactions', [TransactionController::class, 'store'])
    ->addMiddleware(new \App\Middleware\AuthMiddleware());