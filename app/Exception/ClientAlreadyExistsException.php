<?php

namespace App\Exception;

use Exception;

class ClientAlreadyExistsException extends Exception
{
    protected $message = 'Client with this email already exists';
}
