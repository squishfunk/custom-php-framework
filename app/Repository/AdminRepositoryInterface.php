<?php

namespace App\Repository;

use App\Entity\Admin;

interface AdminRepositoryInterface
{
    public function findByEmail(string $email): ?Admin;

    public function save(Admin $admin): void;
}
