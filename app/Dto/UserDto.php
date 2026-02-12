<?php

namespace App\Dto;

class UserDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?float $balance = null
    ) {
    }
}
