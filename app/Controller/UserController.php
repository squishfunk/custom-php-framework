<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Dto\UserDto;
use App\Service\UserService;

class UserController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function store(Request $request)
    {
        $dto = new UserDto(
            $request->input("name"),
            $request->input("email"),
            (float) $request->input("balance")
        );

        $this->userService->createUser($dto);

        return new Response('OK', 201);
    }

    public function update(Request $request, string $id)
    {
        $dto = new UserDto(
            $request->input('name'),
            $request->input('email'),
            $request->input('balance')
        );

        $this->userService->updateUser((int) $id, $dto);

        return new Response('User updated');
    }

    public function show(Request $request, string $id)
    {
        $user = $this->userService->getUser((int) $id);

        if (!$user) {
            return new Response('User not found', 404);
        }

        $userData = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'balance' => $user->getBalance(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
        ];

        return new Response(json_encode($userData));
    }

    public function delete(Request $request, string $id)
    {
        $this->userService->deleteUser((int) $id);
        return new Response('User deleted');
    }

    public function index()
    {
        $users = $this->userService->getAllUsers();

        $usersList = [];
        foreach ($users as $user) {
            $usersList[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'balance' => $user->getBalance(),
            ];
        }

        return new Response(json_encode($usersList));
    }
}