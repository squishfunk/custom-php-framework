<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Request;



abstract class Controller
{

    protected function validate(Request $request, array $rules): array
    {
        $data = $request->all();
        $validator = new Validator();
        if (!$validator->validate($data, $rules)) {

            // remove critical data
            unset($data['password'], $data['password_confirmation']);

            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $data;

            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        return $data;
    }

    protected function render(string $template, array $data = [], int $statusCode = 200): Response
    {
        $data['errors'] = $_SESSION['errors'] ?? [];
        $data['old'] = $_SESSION['old'] ?? [];

        unset($_SESSION['errors'], $_SESSION['old']);

        return new Response(View::render($template, $data), $statusCode);
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}
