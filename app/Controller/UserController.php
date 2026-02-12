<?php

namespace App\Controller;

use App\Core\Response;

class UserController
{
    public function index()
    {
        return new Response('User index');
    }
}