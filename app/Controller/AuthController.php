<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if ($this->authService->login($email, $password)) {
            $this->redirect('/');
        }

        return $this->render('auth/login.html.twig', [
            'error' => 'Invalid credentials'
        ], 401);
    }

    public function showRegister(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    public function register(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        try {
            $this->authService->registerAdmin($email, $password);
            $this->redirect('/login?registered=1');
        } catch (\Exception $e) {
            return $this->render('auth/register.html.twig', [
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function logout()
    {
        $this->authService->logout();
        $this->redirect('/login');
    }
}
