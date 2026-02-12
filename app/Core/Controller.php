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
                'autoescape' => 'html',
                'debug' => true,
            ]);
        }

        return $this->twig;
    }

    /**
     * Render a template and return a Response
     *
     * @param string $template Template path
     * @param array $data Data to pass to template
     * @param int $statusCode HTTP status code
     * @return Response
     */
    protected function render(string $template, array $data = [], int $statusCode = 200): Response
    {
        $content = $this->getTwig()->render($template, $data);

        return new Response($content, $statusCode);
    }
}
