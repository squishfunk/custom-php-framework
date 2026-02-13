<?php

namespace App\Exception;

use Exception;

class InvalidCredentialsException extends Exception
{
    protected $message = 'Invalid credentials';
}
