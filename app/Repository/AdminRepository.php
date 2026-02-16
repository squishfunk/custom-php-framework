<?php

namespace App\Repository;

use App\Core\Database;
use App\Entity\Admin;
use PDO;

class AdminRepository implements AdminRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?Admin
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Admin(
            (int) $data['id'],
            $data['email'],
            $data['password_hash'],
            $data['created_at']
        );
    }

    public function save(Admin $admin): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO admins (email, password_hash) VALUES (:email, :password_hash)'
        );
        $stmt->execute([
            'email' => $admin->getEmail(),
            'password_hash' => $admin->getPasswordHash(),
        ]);
    }
}
