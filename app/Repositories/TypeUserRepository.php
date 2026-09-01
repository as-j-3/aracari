<?php

namespace App\Repositories;

use App\Models\TypeOfUser;
use App\Repositories\Contracts\TypeUserRepositoryInterface;
use Illuminate\Support\Facades\Log;

class TypeUserRepository implements TypeUserRepositoryInterface
{
    protected TypeOfUser $typeOfUser;

    public function __construct(TypeOfUser $typeOfUser)
    {
        $this->typeOfUser = $typeOfUser;
    }

    public function findByCode(string $code): ?TypeOfUser
    {
        return $this->typeOfUser->where('type_user_code', $code)->first();
    }

    public function getCustomerType(): ?TypeOfUser
    {
        $customerType = $this->findByCode(TypeOfUser::CUSTOMER_CODE);

        if (!$customerType) {
            Log::error('Critical: CUSTOMER user type not found in database');
        }

        return $customerType;
    }

    public function getProviderType(): ?TypeOfUser
    {
        $providerType = $this->findByCode(TypeOfUser::PROVIDER_CODE);

        if (!$providerType) {
            Log::error('Critical: PROVIDER user type not found in database');
        }

        return $providerType;
    }

    public function findById(int $id): ?TypeOfUser
    {
        return $this->typeOfUser->find($id);
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->typeOfUser->all();
    }

    public function create(array $data): ?TypeOfUser
    {
        if (!empty($data['type_user_code'])) {
            $existing = $this->findByCode($data['type_user_code']);

            if ($existing) {
                Log::warning('Attempted to create duplicate user type', [
                    'type_code' => $data['type_user_code']
                ]);
                return null;
            }
        }

        return $this->typeOfUser->create($data);
    }

    public function update(int $id, array $data): ?TypeOfUser
    {
        $typeOfUser = $this->typeOfUser->find($id);

        if (!$typeOfUser) {
            return null;
        }

        if (!empty($data['type_user_code']) && $data['type_user_code'] !== $typeOfUser->type_user_code) {
            $existing = $this->findByCode($data['type_user_code']);

            if ($existing) {
                Log::warning('Attempted to update user type to duplicate code', [
                    'type_id' => $id,
                    'requested_code' => $data['type_user_code']
                ]);
                return null;
            }
        }

        $typeOfUser->update($data);
        return $typeOfUser;
    }

    public function delete(int $id): bool
    {
        $typeOfUser = $this->typeOfUser->find($id);

        if (!$typeOfUser) {
            return false;
        }

        $protectedTypes = [
            TypeOfUser::CUSTOMER_CODE,
            TypeOfUser::PROVIDER_CODE,
            TypeOfUser::ADMIN_CODE
        ];

        if (in_array($typeOfUser->type_user_code, $protectedTypes)) {
            Log::warning('Attempted to delete protected user type', [
                'type_id' => $id,
                'type_code' => $typeOfUser->type_user_code
            ]);
            return false;
        }

        if ($typeOfUser->users()->count() > 0) {
            Log::warning('Attempted to delete user type with associated users', [
                'type_id' => $id,
                'user_count' => $typeOfUser->users()->count()
            ]);
            return false;
        }

        return $typeOfUser->delete();
    }
}
