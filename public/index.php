<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\UserController;
use App\Core\Request;
use App\Core\Router;

$request = Request::createFromGlobals();

$router = new Router();

require_once __DIR__ . '/../routes/web.php';

$response = $router->dispatch($request);

$response->send();
