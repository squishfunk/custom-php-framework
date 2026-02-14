<?php 

namespace App\Repository;

use App\Core\Database;
use PDO;

class StatisticRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getTopClientsByBalance(int $limit, string $dateFrom, string $dateTo): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.name, c.email, c.balance
            FROM clients c
            WHERE c.created_at BETWEEN :dateFrom AND :dateTo
            ORDER BY c.balance DESC
            LIMIT :limit"
        );
        $stmt->execute([
            'limit' => $limit,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopClientsByVolume(int $limit, string $dateFrom, string $dateTo): array
    {
        $sql = 'SELECT t.client_id, c.name, SUM(t.amount) as volume 
             FROM transactions t 
             JOIN clients c ON t.client_id = c.id 
             WHERE t.date >= :date_from AND t.date <= :date_to
             GROUP BY t.client_id 
             ORDER BY volume DESC 
             LIMIT :limit';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'limit' => $limit,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}