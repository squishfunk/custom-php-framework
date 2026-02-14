<?php

namespace App\Repository;

use App\Core\Database;
use App\Entity\Transaction;
use App\Entity\Client;
use PDO;

class TransactionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function save(Transaction $transaction): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO transactions (client_id, type, amount, description, date) VALUES (:client_id, :type, :amount, :description, :date)'
        );
        $stmt->execute([
            'client_id' => $transaction->getClientId(),
            'type' => $transaction->getType(),
            'amount' => $transaction->getAmount(),
            'description' => $transaction->getDescription(),
            'date' => $transaction->getDate(),
        ]);
        $transaction->setId((int) $this->db->lastInsertId());
    }

    public function findByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM transactions WHERE client_id = :client_id ORDER BY date DESC, id DESC');
        $stmt->execute(['client_id' => $clientId]);

        $transactions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transactions[] = new Transaction(
                (int) $row['id'],
                (int) $row['client_id'],
                $row['type'],
                (float) $row['amount'],
                $row['description'],
                $row['date'],
                $row['created_at']
            );
        }

        return $transactions;
    }
    public function findTopClientsByVolume(int $limit, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if(!$dateFrom){
            $dateFrom = date('Y-m-d', strtotime('-7 days'));
        }

        if(!$dateTo){
            $dateTo = date('Y-m-d');
        }

        $dateTo = date('Y-m-d 23:59:59', strtotime($dateTo));

        $sql = 'SELECT t.client_id, c.name, SUM(t.amount) as volume 
             FROM transactions t 
             JOIN clients c ON t.client_id = c.id 
             WHERE t.date >= :date_from AND t.date <= :date_to
             GROUP BY t.client_id 
             ORDER BY volume DESC 
             LIMIT :limit';
        
        $params = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'limit' => $limit
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findTopClientsByBalance(int $limit): array
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
}
