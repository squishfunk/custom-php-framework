<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\HttpException;
use Throwable;
use App\Core\Config;

class ExceptionHandler
{
    public function handle(Throwable $e): Response
    {
        $env = Config::get('app.env') ?: 'prod';

        error_log($e->getMessage());
        error_log($e->getTraceAsString());

        if ($env === 'dev') {
            $this->renderDebug($e);
        }

        return $this->renderGeneric($e);
    }

    private function renderDebug(Throwable $e): void
    {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
        throw $e;
    }

    private function renderGeneric(Throwable $e): Response
    {
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();

            if ($statusCode === 404) {
                try {
                    $content = View::render('error/404.html.twig', [
                        'message' => $e->getMessage()
                    ]);
                    return new Response($content, 404);
                } catch (Throwable $twigError) {
                }
            }
            
            return new Response("Error " . $statusCode, $statusCode);
        }

        return new Response("<h1>Something went wrong</h1><p>Please try again later.</p>", 500);
    }
}
