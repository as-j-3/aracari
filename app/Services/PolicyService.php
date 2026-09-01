<?php

namespace App\Services;

use App\Models\Policy;
use App\Models\PolicyCancellationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for Policy operations
 * 
 * Handles business logic for policy management and cancellation rules
 * Follows SOLID principles with clean, maintainable code
 */
class PolicyService
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Create new policy with optional cancellation rules
     * 
     * Business logic:
     * - Validates policy data
     * - Creates policy with creator ID
     * - Creates cancellation rules if type is cancellation
     * - Logs audit trail
     * 
     * @param array $data Policy data including rules
     * @param int $creatorId User ID of the creator
     * @return array Creation result with success status and message
     */
    public function createPolicy(array $data, int $creatorId): array
    {
        try {
            DB::beginTransaction();

            $policy = Policy::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'created_by' => $creatorId,
            ]);

            // Create cancellation rules if provided
            if ($data['type'] === 'cancellation' && !empty($data['rules'])) {
                foreach ($data['rules'] as $rule) {
                    PolicyCancellationRule::create([
                        'policy_id' => $policy->id,
                        'from_hours' => $rule['from_hours'],
                        'to_hours' => $rule['to_hours'],
                        'fee_percentage' => $rule['fee_percentage'],
                    ]);
                }
            }

            DB::commit();

            $this->auditService->log(
                $creatorId,
                'create',
                'policies',
                $policy->id,
                null,
                $policy->toArray()
            );

            return [
                'success' => true,
                'message' => 'تم إنشاء السياسة بنجاح',
                'data' => $policy->load('rules'),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create policy: ' . $e->getMessage(), [
                'policy_data' => $data,
                'creator_id' => $creatorId,
            ]);

            return [
                'success' => false,
                'message' => 'فشل في إنشاء السياسة: ',
            ];
        }
    }

    /**
     * Update policy with cancellation rules
     * 
     * Business logic:
     * - Updates policy information
     * - Updates existing rules, creates new rules, deletes removed rules
     * - Logs audit trail
     * 
     * @param Policy $policy Policy to update
     * @param array $data Updated policy data including rules
     * @return array Update result with success status and message
     */
    public function updatePolicy(Policy $policy, array $data, ?int $actorId = null): array
    {
        try {
            DB::beginTransaction();

            $oldValues = $policy->toArray();

            $policy->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
            ]);

            // Update cancellation rules
            if ($data['type'] === 'cancellation' && !empty($data['rules'])) {
                $existingRuleIds = [];

                foreach ($data['rules'] as $rule) {
                    if (!empty($rule['id'])) {
                        // Update existing rule
                        $existingRule = PolicyCancellationRule::find($rule['id']);
                        if ($existingRule && $existingRule->policy_id === $policy->id) {
                            $existingRule->update([
                                'from_hours' => $rule['from_hours'],
                                'to_hours' => $rule['to_hours'],
                                'fee_percentage' => $rule['fee_percentage'],
                            ]);
                            $existingRuleIds[] = $existingRule->id;
                        }
                    } else {
                        // Create new rule
                        $newRule = PolicyCancellationRule::create([
                            'policy_id' => $policy->id,
                            'from_hours' => $rule['from_hours'],
                            'to_hours' => $rule['to_hours'],
                            'fee_percentage' => $rule['fee_percentage'],
                        ]);
                        $existingRuleIds[] = $newRule->id;
                    }
                }

                // Delete rules that are not in the updated list
                $policy->rules()->whereNotIn('id', $existingRuleIds)->delete();
            } else {
                // Delete all rules if type is not cancellation
                $policy->rules()->delete();
            }

            DB::commit();

            $this->auditService->log(
                $actorId,
                'update',
                'policies',
                $policy->id,
                $oldValues,
                $policy->fresh()->toArray()
            );

            return [
                'success' => true,
                'message' => 'تم تحديث السياسة بنجاح',
                'data' => $policy->load('rules'),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update policy: ' . $e->getMessage(), [
                'policy_id' => $policy->id,
                'policy_data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'فشل في تحديث السياسة: ',
            ];
        }
    }

    /**
     * Delete policy with validation
     * 
     * Business logic:
     * - Validates policy is not used in trips
     * - Deletes associated rules first
     * - Deletes policy
     * - Logs audit trail
     * 
     * @param Policy $policy Policy to delete
     * @return array Deletion result with success status and message
     */
    public function deletePolicy(Policy $policy, ?int $actorId = null): array
    {
        try {
            // Check if policy is in use
            if ($policy->trips()->count() > 0) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن حذف السياسة لأنها مستخدمة في رحلات',
                ];
            }

            $oldValues = $policy->toArray();

            DB::beginTransaction();

            // Delete associated rules first
            $policy->rules()->delete();
            $policy->delete();

            DB::commit();

            $this->auditService->log(
                $actorId,
                'delete',
                'policies',
                $policy->id,
                $oldValues,
                null
            );

            return [
                'success' => true,
                'message' => 'تم حذف السياسة بنجاح',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete policy: ' . $e->getMessage(), [
                'policy_id' => $policy->id,
            ]);

            return [
                'success' => false,
                'message' => 'فشل في حذف السياسة: ',
            ];
        }
    }

    /**
     * Calculate cancellation fee based on hours before trip
     * 
     * Business logic:
     * - Validates policy is cancellation type
     * - Finds applicable rule based on hours before trip
     * - Returns default rule if no specific rule matches
     * 
     * @param int $policyId Policy ID
     * @param int $hoursBefore Hours before trip departure
     * @return array Calculation result with fee percentage
     */
    public function calculateCancellationFee(int $policyId, int $hoursBefore): array
    {
        $policy = Policy::with('rules')->find($policyId);

        if (!$policy) {
            return [
                'success' => false,
                'message' => 'السياسة غير موجودة',
            ];
        }

        if ($policy->type !== 'cancellation') {
            return [
                'success' => false,
                'message' => 'السياسة المحددة ليست سياسة إلغاء',
            ];
        }

        $rule = $policy->rules()
            ->where('from_hours', '<=', $hoursBefore)
            ->where('to_hours', '>=', $hoursBefore)
            ->first();

        if (!$rule) {
            // Find default rule (largest range)
            $rule = $policy->rules()
                ->orderBy('from_hours', 'desc')
                ->first();
        }

        return [
            'success' => true,
            'data' => [
                'hours_before' => $hoursBefore,
                'fee_percentage' => $rule ? $rule->fee_percentage : 100,
                'rule' => $rule
            ]
        ];
    }

    /**
     * Get policies filtered by type
     * 
     * @param string $type Policy type (default: cancellation)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPoliciesByType(string $type = 'cancellation')
    {
        return Policy::where('type', $type)
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();
    }
}
