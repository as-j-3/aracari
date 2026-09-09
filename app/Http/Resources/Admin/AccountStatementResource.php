<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountStatementResource extends JsonResource
{
    private $openingBalance;

    private $statementLines;

    private $closingBalance;

    private $startDate;

    private $endDate;

    private $baseCurrencyCode;

    public function __construct($account, array $data = [])
    {
        parent::__construct($account);

        $this->openingBalance = $data['opening_balance'] ?? null;
        $this->statementLines = $data['statement_lines'] ?? [];
        $this->closingBalance = $data['closing_balance'] ?? null;
        $this->startDate = $data['start_date'] ?? null;
        $this->endDate = $data['end_date'] ?? null;
        $this->baseCurrencyCode = $data['base_currency_code'] ?? '-';
    }

    public function toArray(Request $request): array
    {
        return [
            'account' => [
                'id' => $this->id,
                'uuid' => $this->uuid,
                'account_code' => $this->account_code,
                'account_name' => $this->account_name,
                'account_type' => $this->account_type,
                'level' => $this->level,
            ],
            'opening_balance' => $this->openingBalance,
            'statement_lines' => $this->statementLines,
            'closing_balance' => $this->closingBalance,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'base_currency_code' => $this->baseCurrencyCode,
        ];
    }
}
