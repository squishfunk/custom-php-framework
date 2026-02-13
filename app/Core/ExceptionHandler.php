<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\HttpException;
use Throwable;

class ExceptionHandler
{
    public function handle(Throwable $e): Response
    {
        if ($e instanceof HttpException) {

            if ($e->getStatusCode() === 404) {
                try {
                    $content = View::render('error/404.html.twig', [
                        'message' => $e->getMessage()
                    ]);
                    return new Response($content, 404);
                } catch (Throwable $twigError) {
                    return new Response("404 Not Found: " . $e->getMessage(), 404);
                }
            }

            return new Response($e->getMessage(), );
        }

        return new Response(
            "500 Internal Server Error: " . $e->getMessage(),
            500
        );
    }
}
