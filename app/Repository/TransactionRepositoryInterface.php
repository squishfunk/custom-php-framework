<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\Client;

interface TransactionRepositoryInterface
{
    public function save(Transaction $transaction): void;

    public function findByClientId(int $clientId): array;

    public function findTopClientsByVolume(int $limit, ?string $dateFrom = null, ?string $dateTo = null): array;

    public function findTopClientsByBalance(int $limit): array;
}
