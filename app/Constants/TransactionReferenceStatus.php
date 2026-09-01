<?php

namespace App\Constants;

class TransactionReferenceStatus
{
    public const DRAFT = 'draft';
    public const CONFIRMED = 'confirmed';
    public const REVERSED = 'reversed';
    public const CANCELLED = 'cancelled';
}