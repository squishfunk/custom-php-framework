<?php

namespace App\Exception;

use Exception;

class InsufficientBalanceException extends Exception
{
    protected $message = 'Insufficient balance: operation would result in negative balance';
}
