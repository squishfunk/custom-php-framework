<?php

declare(strict_types=1);

namespace App\Dto;

class RegisterDto
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }
}
