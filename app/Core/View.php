<?php

declare(strict_types=1);

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

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

            self::$twig->addFunction(new TwigFunction('csrf_token', function () {
                return CsrfToken::getToken();
            }));

            self::$twig->addFunction(new TwigFunction('csrf_field', function () {
                $token = CsrfToken::getToken();
                return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
            }, ['is_safe' => ['html']]));
        }

        return self::$twig;
    }

    public static function render(string $template, array $data = []): string
    {
        return self::getTwig()->render($template, $data);
    }
}
