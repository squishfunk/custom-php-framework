<?php

namespace App\Exception;

use Exception;

class ClientNotFoundException extends Exception
{
    protected $message = 'Client not found';
}
