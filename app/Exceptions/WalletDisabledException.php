<?php

namespace App\Exceptions;

use RuntimeException;

class WalletDisabledException extends RuntimeException
{
    public function __construct(string $message = 'نظام المحفظة غير مفعل حالياً.')
    {
        parent::__construct($message);
    }
}