<?php

declare(strict_types=1);

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    protected ?Environment $twig = null;

    /**
     * Initialize Twig
     */
    protected function getTwig(): Environment
    {
        if ($this->twig === null) {
            $loader = new FilesystemLoader(__DIR__ . '/../../templates');
            $this->twig = new Environment($loader, [
                'cache' => false,
                'debug' => true,
            ]);
            $this->twig->addGlobal('session', $_SESSION ?? []);
        }

        return $this->twig;
    }

    protected function render(string $template, array $data = [], int $statusCode = 200): Response
    {
        return new Response($this->getTwig()->render($template, $data), $statusCode);
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}
