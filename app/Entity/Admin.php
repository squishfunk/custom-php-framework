<?php

namespace App\Entity;

class Admin
{
    private int $id;
    private string $email;
    private string $passwordHash;
    private string $createdAt;

    public function __construct(
        int $id,
        string $email,
        string $passwordHash,
        string $createdAt
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
    }

    public static function create(string $email, string $password): self
    {
        return new self(
            0,
            $email,
            password_hash($password, PASSWORD_DEFAULT), 
            date('Y-m-d H:i:s')
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
}
