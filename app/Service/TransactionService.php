<?php

namespace App\Service;

use App\Core\Database;
use App\Entity\Transaction;
use App\Exception\ClientNotFoundException;
use App\Repository\ClientRepository;
use App\Repository\TransactionRepository;
use App\Dto\TransactionDto;
use PDOException;
use Exception;

class TransactionService
{
    private TransactionRepository $transactionRepository;
    private ClientRepository $clientRepository;

    public function __construct()
    {
        $this->transactionRepository = new TransactionRepository();
        $this->clientRepository = new ClientRepository();
    }

    public function addTransaction(TransactionDto $dto): void
    {
        $client = $this->clientRepository->find($dto->clientId);

        if (!$client) {
            throw new ClientNotFoundException();
        }

        $currentBalance = $client->getBalance();
        $newBalance = $this->calculateNewBalance($currentBalance, $dto->type, $dto->amount);

        Database::beginTransaction();

        try {
            $transaction = Transaction::create(
                $dto->clientId,
                $dto->type,
                $dto->amount,
                $dto->description,
                $dto->date
            );
            $this->transactionRepository->save($transaction);

            $client->setBalance($newBalance);
            $this->clientRepository->update($client);

            Database::commit();
        } catch (PDOException $e) {
            Database::rollBack();
            throw new Exception('Failed to process transaction: ' . $e->getMessage());
        }
    }

    private function calculateNewBalance(float $currentBalance, string $type, float $amount): float
    {
        return match ($type) {
            'expense' => $currentBalance - $amount,
            'earning' => $currentBalance + $amount,
            default => throw new Exception('Invalid transaction type: ' . $type),
        };
    }

    public function getClientTransactions(int $clientId): array
    {
        return $this->transactionRepository->findByClientId($clientId);
    }
}
