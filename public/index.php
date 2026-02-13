<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\Request;
use App\Core\Router;
use App\Core\ExceptionHandler;
use Throwable;

$request = Request::createFromGlobals();

$router = new Router();

require_once __DIR__ . '/../routes/web.php';

try {
    $response = $router->dispatch($request);
} catch (Throwable $e) {
    $handler = new ExceptionHandler();
    $response = $handler->handle($e);
}

$response->send();
