<?php

namespace App\Entity;

class Transaction
{
    private int $id;
    private int $clientId;
    private string $type;
    private float $amount;
    private ?string $description;
    private string $date;
    private string $createdAt;

    public function __construct(
        int $id,
        int $clientId,
        string $type,
        float $amount,
        ?string $description,
        string $date,
        string $createdAt
    ) {
        $this->id = $id;
        $this->clientId = $clientId;
        $this->type = $type;
        $this->amount = $amount;
        $this->description = $description;
        $this->date = $date;
        $this->createdAt = $createdAt;
    }

    public static function create(int $clientId, string $type, float $amount, ?string $description, string $date): self
    {
        return new self(
            0,
            $clientId,
            $type,
            $amount,
            $description,
            $date,
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

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
