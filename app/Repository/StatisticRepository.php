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

    public function getTopClientsByBalance(int $limit, ?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom && $dateTo) {
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
        } else {
            $stmt = $this->db->prepare(
                "SELECT c.id, c.name, c.email, c.balance
                FROM clients c
                ORDER BY c.balance DESC
                LIMIT :limit"
            );
            $stmt->execute([
                'limit' => $limit,
            ]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopClientsByVolume(int $limit, ?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom && $dateTo) {
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
        } else {
            $sql = 'SELECT t.client_id, c.name, SUM(t.amount) as volume 
                 FROM transactions t 
                 JOIN clients c ON t.client_id = c.id 
                 GROUP BY t.client_id 
                 ORDER BY volume DESC 
                 LIMIT :limit';
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'limit' => $limit,
            ]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransactionTypeDistribution(?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom && $dateTo) {
            $sql = 'SELECT t.type, SUM(t.amount) as total, COUNT(*) as count 
                 FROM transactions t 
                 WHERE t.date >= :date_from AND t.date <= :date_to
                 GROUP BY t.type';
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
        } else {
            $sql = 'SELECT t.type, SUM(t.amount) as total, COUNT(*) as count 
                 FROM transactions t 
                 GROUP BY t.type';
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailyTransactionTrend(?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom && $dateTo) {
            $sql = 'SELECT DATE(t.date) as date, SUM(t.amount) as total, COUNT(*) as count 
                 FROM transactions t 
                 WHERE t.date >= :date_from AND t.date <= :date_to
                 GROUP BY DATE(t.date)
                 ORDER BY date ASC';
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
        } else {
            $sql = 'SELECT DATE(t.date) as date, SUM(t.amount) as total, COUNT(*) as count 
                 FROM transactions t 
                 GROUP BY DATE(t.date)
                 ORDER BY date ASC';
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalMarketCap(?string $dateFrom, ?string $dateTo): array
    {
        if ($dateFrom && $dateTo) {
            $sql = 'SELECT 
                day,
                SUM(daily_net) OVER (ORDER BY day) AS total_company_value
            FROM (
                SELECT
                    DATE(t.date) AS day,
                    SUM(
                        CASE
                            WHEN t.type = "earning" THEN t.amount
                            WHEN t.type = "expense" THEN -t.amount
                        END
                    ) AS daily_net
                FROM transactions t
                WHERE t.date >= :date_from AND t.date <= :date_to
                GROUP BY day
            ) t
            ORDER BY day';
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
        } else {
            $sql = 'SELECT 
                day,
                SUM(daily_net) OVER (ORDER BY day) AS total_company_value
            FROM (
                SELECT
                    DATE(t.date) AS day,
                    SUM(
                        CASE
                            WHEN t.type = "earning" THEN t.amount
                            WHEN t.type = "expense" THEN -t.amount
                        END
                    ) AS daily_net
                FROM transactions t
                GROUP BY day
            ) t
            ORDER BY day';
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCapitalDistribution(int $limit): array
    {
        $sql = 'SELECT 
            c.id,
            c.name,
            c.balance,
            ROUND(
                c.balance / (SELECT SUM(balance) FROM clients WHERE balance > 0) * 100,
                2
            ) AS percentage
        FROM clients c
        WHERE c.balance > 0
        ORDER BY c.balance DESC
        LIMIT :limit';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'limit' => $limit,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}