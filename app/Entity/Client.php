<?php

namespace App\Entity;

class Client
{
    private int $id;
    private string $name;
    private string $email;
    private float $balance;
    private string $created_at;
    private string $updated_at;

    public function __construct(
        int $id,
        string $name,
        string $email,
        float $balance,
        string $created_at,
        string $updated_at
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->balance = $balance;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function create(string $name, string $email, float $balance): self
    {
        return new self(
            0,
            $name,
            $email,
            $balance,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): string
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(string $updated_at): void
    {
        $this->updated_at = $updated_at;
    }
}