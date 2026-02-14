<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\Request;
use App\Core\Router;
use App\Core\ExceptionHandler;
use App\Core\Config;
use Dotenv\Dotenv;
use Throwable;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

Config::load(__DIR__ . '/../config/config.php');

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
