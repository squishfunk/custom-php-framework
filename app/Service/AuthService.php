<?php

namespace App\Service;

use App\Entity\Admin;
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
            throw new \Exception("Invalid email or password");
        }

        if ($admin->verifyPassword($password)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['admin_id'] = $admin->getId();
            $_SESSION['admin_email'] = $admin->getEmail();
            return true;
        }

        throw new \Exception("Invalid email or password");
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
            throw new \RuntimeException("Admin already exists");
        }

        $admin = Admin::create($email, $password);
        $this->adminRepository->save($admin);
    }
}
