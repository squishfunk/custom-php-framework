<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Repository\ClientRepository;
use App\Repository\TransactionRepository;

class TransactionService
{
    private TransactionRepository $transactionRepository;
    private ClientRepository $clientRepository;

    public function __construct()
    {
        $this->transactionRepository = new TransactionRepository();
        $this->clientRepository = new ClientRepository();
    }

    // TODO DTO
    public function addTransaction(int $clientId, string $type, float $amount, ?string $description, string $date): void
    {
        $client = $this->clientRepository->find($clientId);

        if (!$client) {
            throw new \RuntimeException("Client not found");
        }

        $transaction = Transaction::create($clientId, $type, $amount, $description, $date);
        $this->transactionRepository->save($transaction);

        // Update Client Balance
        $currentBalance = $client->getBalance();
        if ($type === 'expense') {
            $client->setBalance($currentBalance - $amount);
        } else {
            $client->setBalance($currentBalance + $amount);
        }
        $this->clientRepository->update($client);
    }

    public function getClientTransactions(int $clientId): array
    {
        return $this->transactionRepository->findByClientId($clientId);
    }
}
