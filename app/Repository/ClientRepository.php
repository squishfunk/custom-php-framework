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

    public function findTopByBalance(int $limit): array
    {
        $stmt = $this->db->prepare('SELECT * FROM clients ORDER BY balance DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $clients = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $clients[] = new Client(
                $row['id'],
                $row['name'],
                $row['email'],
                (float) $row['balance'],
                $row['created_at'],
                $row['updated_at']
            );
        }

        return $clients;
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
