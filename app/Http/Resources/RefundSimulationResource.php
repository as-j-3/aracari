<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundSimulationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'booking_info' => [
                'uuid' => $this['booking_info']['uuid'] ?? null,
                'total_paid' => $this['booking_info']['total_paid'] ?? 0,
                'total_tax' => $this['booking_info']['total_tax'] ?? 0,
                'total_commission' => $this['booking_info']['total_commission'] ?? 0,
                'total_amount' => $this['booking_info']['total_amount'] ?? 0,
            ],
            'refund_transactions' => [
                'total_refund_to_customer' => $this['refund_transactions']['total_refund_to_customer'] ?? 0,
                'total_debit_from_revenue' => $this['refund_transactions']['total_debit_from_revenue'] ?? 0,
                'total_credit_penalties' => $this['refund_transactions']['total_credit_penalties'] ?? 0,
                'total_tax_refund' => $this['refund_transactions']['total_tax_refund'] ?? 0,
                'total_commission_refund' => $this['refund_transactions']['total_commission_refund'] ?? 0,
            ],
            'verification' => [
                'should_equal' => $this['verification']['should_equal'] ?? 0,
                'actual_total_refunded' => $this['verification']['actual_total_refunded'] ?? 0,
                'is_balanced' => $this['verification']['is_balanced'] ?? false,
                'difference' => $this['verification']['difference'] ?? 0,
            ],
            'transactions_count' => $this['transactions_count'] ?? 0,
            'transactions' => $this['transactions'] ?? [],
        ];
    }
}