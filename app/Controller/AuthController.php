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
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);



        if ($this->authService->login($data['email'], $data['password'])) {
            $this->redirect('/login');
        }

        return $this->render('auth/login.html.twig', [
            'error' => 'Invalid credentials',
            'old' => ['email' => $data['email']]
        ], 401);
    }

    public function showRegister(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    public function register(Request $request)
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        try {
            $this->authService->registerAdmin($data['email'], $data['password']);
            $this->redirect('/login');
        } catch (\Exception $e) {
            return $this->render('auth/register.html.twig', [
                'error' => $e->getMessage(),
                'old' => $data
            ], 400);
        }
    }

    public function logout()
    {
        $this->authService->logout();
        $this->redirect('/login');
    }
}
