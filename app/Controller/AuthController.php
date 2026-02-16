<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Exception\AdminAlreadyExistsException;
use App\Exception\InvalidCredentialsException;
use App\Service\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        $this->authService = $authService ?? new AuthService();
    }

    public function showLogin(): Response
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('/');
        }

        return $this->render('auth/login.html.twig');
    }

    public function login(Request $request)
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);



        try {
            if ($this->authService->login($data['email'], $data['password'])) {
                $this->redirect('/');
            }
        } catch (InvalidCredentialsException $e) {
            return $this->render('auth/login.html.twig', [
                'error' => 'Invalid credentials',
                'old' => ['email' => $data['email']]
            ], 401);
        }
    }

    public function showRegister(): Response
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('/');
        }
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
        } catch (AdminAlreadyExistsException $e) {
            return $this->render('auth/register.html.twig', [
                'error' => $e->getMessage(),
                'old' => $data
            ], 400);
        } catch (\Exception $e) {
            return $this->render('auth/register.html.twig', [
                'error' => 'Registration failed',
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
