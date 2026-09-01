<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

trait HasAudit
{

    protected static function bootHasAudit(): void
    {
        static::created(function ($model): void {
            self::logAudit('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model): void {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getChanges());
            $newValues = $model->getChanges();

            if (empty($oldValues) && empty($newValues)) {
                return;
            }

            self::logAudit('updated', $model, $oldValues, $newValues);
        });

        static::deleted(function ($model): void {
            self::logAudit('deleted', $model, $model->getAttributes(), null);
        });
    }


    protected static function logAudit(string $action, $model, ?array $old, ?array $new): void
    {
        $service = app(AuditService::class);
        
        $userId = auth()->id();
        
        $service->logModelEvent(
            $userId,
            $action,
            $model->getTable(),
            (int) ($model->getKey() ?? 0),
            $old,
            $new
        );
    }
}