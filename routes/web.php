<?php

use App\Core\Router;
use App\Controller\UserController;

/** @var Router $router */

$router->get('/', [UserController::class, 'index']);