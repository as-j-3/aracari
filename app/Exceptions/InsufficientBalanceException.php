<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct(string $message = 'عذراً، رصيدك غير كافٍ')
    {
        parent::__construct($message);
    }
}
