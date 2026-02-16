<?php

namespace App\Repository;

use App\Entity\Client;

interface ClientRepositoryInterface
{
    /**
     * @return Client[]
     */
    public function findAll(): array;

    public function findPaginated(int $page, int $perPage): array;

    public function findByEmail(string $email): ?Client;

    public function findByEmailExceptId(string $email, int $excludeId): ?Client;

    public function find(int $id): Client;

    public function save(Client $client): void;

    public function update(Client $client): void;

    public function delete(int $id): void;
}
