<?php

use App\Core\Router;
use App\Controller\UserController;

/** @var Router $router */

$router->get('/', [UserController::class, 'index']);
$router->post('/', [UserController::class, 'store']);
$router->get('/{id}', [UserController::class, 'show']);
$router->post('/{id}', [UserController::class, 'update']);
$router->post('/{id}/delete', [UserController::class, 'delete']);