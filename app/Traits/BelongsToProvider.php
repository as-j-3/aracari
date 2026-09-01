<?php

namespace App\Traits;

use App\Models\User;
use App\Services\ProviderContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;


trait BelongsToProvider
{
    protected static function bootBelongsToProvider(): void
    {
        static::addGlobalScope('provider_isolation', function (Builder $builder): void {
            if (app()->runningInConsole()) {
                return;
            }

            if (!Auth::check()) {
                return;
            }

            $user = Auth::user();
            if (!$user instanceof User || $user->isAdmin()) {
                return;
            }

            $model = $builder->getModel();
            $scopeValue = $model->resolveProviderScopeValue($user);

            if ($scopeValue === null) {
                return;
            }

            $relationPath = $model->getProviderOwnershipRelationPath();
            if ($relationPath) {
                $builder->whereHas($relationPath, function (Builder $relationQuery) use ($model, $scopeValue): void {
                    $relationQuery->withoutGlobalScopes()->where(
                        $model->getProviderOwnershipRelationKeyName(),
                        $scopeValue
                    );
                });

                return;
            }

            $builder->where(
                $model->qualifyColumn($model->getProviderOwnershipColumnName()),
                $scopeValue
            );
        });
    }

    public function scopeWithoutProviderIsolation(Builder $query): Builder
    {
        return $query->withoutGlobalScope('provider_isolation');
    }

    public function isOwnedByProvider(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $scopeValue = $this->resolveProviderScopeValue($user);
        if ($scopeValue === null) {
            return false;
        }

        $relationPath = $this->getProviderOwnershipRelationPath();
        if ($relationPath) {
            return (bool) $this->newQueryWithoutScopes()
                ->whereKey($this->getKey())
                ->whereHas($relationPath, function (Builder $query) use ($scopeValue): void {
                    $query->withoutGlobalScopes()->where($this->getProviderOwnershipRelationKeyName(), $scopeValue);
                })
                ->exists();
        }

        return (string) $this->getAttribute($this->getProviderOwnershipColumnName()) === (string) $scopeValue;
    }

    public function getProviderOwnershipColumnName(): string
    {
        return property_exists($this, 'providerOwnershipColumn')
            ? $this->providerOwnershipColumn
            : 'provider_id';
    }

    public function getProviderOwnershipRelationPath(): ?string
    {
        return property_exists($this, 'providerOwnershipRelation')
            ? $this->providerOwnershipRelation
            : null;
    }

    public function getProviderOwnershipRelationKeyName(): string
    {
        return property_exists($this, 'providerOwnershipRelationKey')
            ? $this->providerOwnershipRelationKey
            : 'id';
    }

    public function resolveProviderScopeValue(User $user): mixed
    {
        $context = app(ProviderContext::class);

        if (property_exists($this, 'providerOwnershipScope')) {
            return match ($this->providerOwnershipScope) {
                'provider_user' => $context->providerUserIdFor($user),
                default => $context->providerServiceIdFor($user),
            };
        }

        return $context->providerServiceIdFor($user);
    }
}
