<?php

namespace App\Repository;

use App\Core\Database;
use App\Entity\Client;
use App\Exception\ClientNotFoundException;
use PDO;

class ClientRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @return Client[]
     */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM clients');
        $users = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = new Client(
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
    
    public function findByEmail(string $email): ?Client
    {
        $stmt = $this->db->prepare('SELECT * FROM clients WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Client(
            (int) $row['id'],
            $row['name'],
            $row['email'],
            (float) $row['balance'],
            $row['created_at'],
            $row['updated_at']
        );
    }

    public function findByEmailExceptId(string $email, int $excludeId): ?Client
    {
        $stmt = $this->db->prepare('SELECT * FROM clients WHERE email = :email AND id != :id');
        $stmt->execute(['email' => $email, 'id' => $excludeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Client(
            (int) $row['id'],
            $row['name'],
            $row['email'],
            (float) $row['balance'],
            $row['created_at'],
            $row['updated_at']
        );
    }

    public function find(int $id): Client
    {
        $stmt = $this->db->prepare('SELECT * FROM clients WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw new ClientNotFoundException("Client with id $id not found");
        }

        return new Client(
            (int) $data['id'],
            $data['name'],
            $data['email'],
            (float) $data['balance'],
            $data['created_at'],
            $data['updated_at']
        );
    }

    public function save(Client $client): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO clients (name, email, balance) VALUES (:name, :email, :balance)'
        );
        $stmt->execute([
            'name' => $client->getName(),
            'email' => $client->getEmail(),
            'balance' => $client->getBalance(),
        ]);
        $client->setId((int) $this->db->lastInsertId());
    }

    public function update(Client $client): void
    {
        $stmt = $this->db->prepare(
            'UPDATE clients SET name = :name, email = :email, balance = :balance WHERE id = :id'
        );
        $stmt->execute([
            'id' => $client->getId(),
            'name' => $client->getName(),
            'email' => $client->getEmail(),
            'balance' => $client->getBalance(),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM clients WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
