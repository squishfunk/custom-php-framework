<?php

namespace App\Repository;

use App\Core\Database;
use App\Entity\User;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @return User[]
     */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM users');
        $users = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = new User(
                (int) $row['id'],
                $row['name'],
                $row['email'],
                (float) $row['balance'],
                $row['created_at'],
                $row['updated_at']
            );
        }

        return $users;
    }

    public function find(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new User(
            (int) $data['id'],
            $data['name'],
            $data['email'],
            (float) $data['balance'],
            $data['created_at'],
            $data['updated_at']
        );
    }

    public function save(User $user): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, balance) VALUES (:name, :email, :balance)'
        );
        $stmt->execute([
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'balance' => $user->getBalance(),
        ]);
    }

    public function update(User $user): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET name = :name, email = :email, balance = :balance WHERE id = :id'
        );
        $stmt->execute([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'balance' => $user->getBalance(),
        ]);
    }
}
