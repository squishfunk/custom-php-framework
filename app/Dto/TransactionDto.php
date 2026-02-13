<?php

declare(strict_types=1);

namespace App\Dto;

class TransactionDto
{
    public function __construct(
        public int $clientId,
        public string $type,
        public float $amount,
        public ?string $description,
        public string $date
    ) {
    }
}
