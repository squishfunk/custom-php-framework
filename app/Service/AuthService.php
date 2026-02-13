<?php

namespace App\Service;

use App\Entity\Admin;
use App\Exception\AdminAlreadyExistsException;
use App\Exception\InvalidCredentialsException;
use App\Repository\AdminRepository;

class AuthService
{
    private AdminRepository $adminRepository;

    public function __construct()
    {
        $this->adminRepository = new AdminRepository();
    }

    public function login(string $email, string $password): bool
    {
        $admin = $this->adminRepository->findByEmail($email);

        if (!$admin) {
            throw new InvalidCredentialsException();
        }

        if ($admin->verifyPassword($password)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            session_regenerate_id(true);
            
            $_SESSION['admin_id'] = $admin->getId();
            $_SESSION['admin_email'] = $admin->getEmail();
            return true;
        }

        throw new InvalidCredentialsException();
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }

    public function registerAdmin(string $email, string $password): void
    {
        if ($this->adminRepository->findByEmail($email)) {
            throw new AdminAlreadyExistsException();
        }

        $admin = Admin::create($email, $password);
        $this->adminRepository->save($admin);
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['admin_id']);
    }
}
