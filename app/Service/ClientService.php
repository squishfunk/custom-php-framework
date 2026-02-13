<?php

namespace App\Service;

use App\Dto\ClientDto;
use App\Entity\Client;
use App\Repository\ClientRepository;

class ClientService
{
    private ClientRepository $clientRepository;

    public function __construct()
    {
        $this->clientRepository = new ClientRepository();
    }

    public function createClient(ClientDto $dto): void
    {
        $client = Client::create($dto->name, $dto->email, $dto->balance);
        $this->clientRepository->save($client);
    }

    public function getClient(int $id): ?Client
    {
        return $this->clientRepository->find($id);
    }

    /**
     * @return Client[]
     */
    public function getAllClients(): array
    {
        return $this->clientRepository->findAll();
    }

    public function updateClient(int $id, ClientDto $dto): void
    {
        $client = $this->clientRepository->find($id);

        if (!$client) {
            throw new \RuntimeException("Client with id $id not found");
        }

        if ($dto->name !== null) {
            $client->setName($dto->name);
        }

        if ($dto->email !== null) {
            $client->setEmail($dto->email);
        }

        if ($dto->balance !== null) {
            $client->setBalance($dto->balance);
        }

        $this->clientRepository->update($client);
    }

    public function deleteClient(int $id): void
    {
        $this->clientRepository->delete($id);
    }
}
