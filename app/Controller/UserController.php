<?php

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Entity\User;
use App\Repository\UserRepository;

class UserController
{

    public function store(Request $request)
    {
        $name = $request->input("name");
        $email = $request->input("email");
        $balance = $request->input("balance");

        $user = User::create($name, $email, $balance);

        $userRepository = new UserRepository();
        $userRepository->save($user);

        return new Response('OK', 201);
    }
    public function index()
    {


        return new Response('User index');
    }
}