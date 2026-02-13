<?php

namespace App\Exception;

use Exception;

class AdminAlreadyExistsException extends Exception
{
    protected $message = 'Admin already exists';
}
