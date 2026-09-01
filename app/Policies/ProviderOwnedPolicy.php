<?php

namespace App\Policies;

use App\Models\User;


class ProviderOwnedPolicy
{

    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }


    public function viewAny(User $user): bool
    {
        if ($user->isProvider()) {
            return true;
        }

        if ($user->parent?->isProvider()) {
            return true;
        }

        if ($user->hasPermission('providers:view-any')) {
            return true;
        }

        return false;
    }


    public function view(User $user, mixed $model): bool
    {
        return $this->owns($user, $model);
    }


    public function update(User $user, mixed $model): bool
    {
        return $this->owns($user, $model);
    }


    public function delete(User $user, mixed $model): bool
    {
        return $this->owns($user, $model);
    }


    protected function owns(User $user, mixed $model): bool
    {
        if (method_exists($model, 'isOwnedByProvider')) {
            return $model->isOwnedByProvider($user);
        }

        if (isset($model->provider_id) && $user->isProvider()) {
            return $model->provider_id === $user->id;
        }

        if (isset($model->user_id) && $user->isProvider()) {
            return $model->user_id === $user->id;
        }

        return false;
    }
}
