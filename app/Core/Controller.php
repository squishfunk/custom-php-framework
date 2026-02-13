<?php

declare(strict_types=1);

namespace App\Core;



abstract class Controller
{

    protected function render(string $template, array $data = [], int $statusCode = 200): Response
    {
        return new Response(View::render($template, $data), $statusCode);
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}
