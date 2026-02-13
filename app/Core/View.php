<?php

declare(strict_types=1);

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{
    private static ?Environment $twig = null;

    private static function getTwig(): Environment
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader(__DIR__ . '/../../templates');
            self::$twig = new Environment($loader, [
                'cache' => false,
                'debug' => true,
            ]);
            self::$twig->addGlobal('session', $_SESSION ?? []);
        }

        return self::$twig;
    }

    public static function render(string $template, array $data = []): string
    {
        return self::getTwig()->render($template, $data);
    }
}
