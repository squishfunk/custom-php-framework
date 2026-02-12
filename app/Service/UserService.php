<?php

namespace App\Service;

use App\Dto\UserDto;
use App\Entity\User;
use App\Repository\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function createUser(UserDto $dto): void
    {
        $user = User::create($dto->name, $dto->email, $dto->balance);
        $this->userRepository->save($user);
    }

    public function getUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * @return User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function updateUser(int $id, UserDto $dto): void
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new \RuntimeException("User with id $id not found");
        }

        if ($dto->name !== null) {
            $user->setName($dto->name);
        }

        if ($dto->email !== null) {
            $user->setEmail($dto->email);
        }

        if ($dto->balance !== null) {
            $user->setBalance($dto->balance);
        }

        $this->userRepository->update($user);
    }

    public function deleteUser(int $id): void
    {
        $this->userRepository->delete($id);
    }
}
