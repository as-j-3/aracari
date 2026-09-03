<?php

namespace App\Services;

use App\Jobs\PersistAuditLog;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class AuditService
{
    private const ALLOWED_ACTIONS = ['created', 'updated', 'deleted', 'approved', 'restored', 'cancelled', 'assign_permissions', 'toggle_status'];

    /**
     * قائمة الحقول الحساسة - يتم إخفاؤها من الـ audit logs
     *  تم تحديثها لتشمل كل الحقول الحساسة المعروفة
     */
    private const SENSITIVE_FIELDS = [
        // كلمات المرور والتوكنات
        'password',
        'password_confirmation',
        'old_password',
        'new_password',
        'current_password',
        'remember_token',
        'reset_token',
        'verification_token',
        // OTP والرموز
        'code',
        'otp',
        'verification_code',
        // التوكنات والـ API keys
        'access_token',
        'device_token',
        'fcm_token',
        'api_token',
        'twilio_token',
        'webhook_secret',
        'secret',
        'private_key',
        // المعلومات الشخصية الحساسة
        'personal_card_id',
        'personal_card_id_photo',
        'passport_number',
        'passport_photo',
        // المعلومات المالية
        'account_number',
        'iban',
        'credit_card',
        'cvv',
        'card_number',
        'bank_account',
    ];

    /**
     * Record an audit log entry
     * 
     * @param int|null $performerId User ID who performed the action
     * @param string $action Action name (e.g., 'update', 'delete', 'approve')
     * @param string $tableName Table being affected
     * @param int $recordId ID of the affected record
     * @param array|null $oldValues Data before change
     * @param array|null $newValues Data after change
     * @return AuditLog|null
     */
    public function log(?int $performerId, string $action, string $tableName, ?int $recordId, ?array $oldValues = null, ?array $newValues = null): ?AuditLog
    {
        try {
            PersistAuditLog::dispatch(
                $performerId,
                $this->normalizeAction($action),
                $tableName,
                $recordId,
                $this->sanitizeData($oldValues),
                $this->sanitizeData($newValues),
                now()->toDateTimeString(),
            )->afterCommit();

            return null;
        } catch (\Throwable $e) {
            Log::error("Failed to record audit log: " . $e->getMessage());
            return null;
        }
    }

    public function logModelEvent(?int $performerId, string $action, string $tableName, int $recordId, ?array $oldValues = null, ?array $newValues = null): ?AuditLog
    {
        return $this->log($performerId, $action, $tableName, $recordId, $oldValues, $newValues);
    }

    public function logPriceChangeProposal(?int $performerId, string $tableName, int $recordId, array $oldValues, array $newValues, float $maxDelta): ?AuditLog
    {
        return $this->log(
            $performerId,
            'updated',
            $tableName,
            $recordId,
            $oldValues,
            [
                'approval_status' => 'pending',
                'proposal_type' => 'price_change',
                'max_delta_percentage' => $maxDelta,
                'proposed_values' => $newValues,
            ]
        );
    }

    private function normalizeAction(string $action): string
    {
        $normalized = strtolower($action);

        // إجراءات محددة معروفة
        $actionMap = [
            'create' => 'created',
            'created' => 'created',
            'store' => 'created',
            'update' => 'updated',
            'updated' => 'updated',
            'cancel' => 'cancelled',
            'cancelled' => 'cancelled',
            'cancel_booking' => 'cancelled',
            'delete' => 'deleted',
            'deleted' => 'deleted',
            'destroy' => 'deleted',
            'force_delete' => 'deleted',
            'restore' => 'restored',
            'restored' => 'restored',
            'approve' => 'approved',
            'approved' => 'approved',
            'activate' => 'approved',
            'assign_permissions' => 'assign_permissions',
            'toggle_status' => 'toggle_status',
            'toggle-status' => 'toggle_status',
        ];

        return $actionMap[$normalized] ?? (in_array($normalized, self::ALLOWED_ACTIONS, true) ? $normalized : 'updated');
    }

    /**
     *  فلترة recursive للحقول الحساسة (تشمل nested arrays)
     */
    private function sanitizeData(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        $result = [];
        foreach ($data as $key => $value) {
            //  تخطّي الحقول الحساسة على المستوى الحالي
            if (in_array($key, self::SENSITIVE_FIELDS, true)) {
                $result[$key] = '[REDACTED]';
                continue;
            }

            //  فلتر recursive للـ nested arrays
            if (is_array($value)) {
                $result[$key] = $this->sanitizeData($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
   
}
