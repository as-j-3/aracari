<?php

namespace App\Repositories;

use App\Models\User;
use App\Constants\NumericConstants;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function all(int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->user->withTrashed()->with(['status:id,status_code', 'userType'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUsers(array $filters = [],int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->user->withTrashed()->with(['status:id,status_code', 'userType']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['type_code'])) {
            $query->where('type_user_code', $filters['type_code']);
        }

        if (!empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (isset($filters['is_approved'])) {
            $query->where('is_approved', (bool)$filters['is_approved']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return $this->user->withTrashed()->with(['status:id,status_code', 'userType'])
            ->find($id);
    }

    public function findByUuid(string $uuid): ?User
    {
        return $this->user->withTrashed()->with(['status:id,status_code', 'userType'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findByPhoneNumber(string $phoneNumber): ?User
    {
        return $this->user->with(['status:id,status_code', 'userType'])
            ->where('phone_number', $phoneNumber)
            ->first();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->user->with(['status:id,status_code', 'userType'])
            ->where('email', $email)
            ->first();
    }

    public function create(array $data): User
    {
        return $this->user->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = $this->user->withTrashed()->find($id);

        return $user ? $user->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $user = $this->user->withTrashed()->find($id);

        return $user ? $user->delete() : false;
    }

    public function getByType(string $typeCode, int $perPage =NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->user->with(['status:id,status_code', 'userType'])
            ->where('type_user_code', $typeCode)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getByStatus(string $statusCode, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->user->with(['status:id,status_code', 'userType'])
            ->whereHas('status', function ($query) use ($statusCode) {
                $query->where('status_code', $statusCode);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function search(string $search, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->user->with(['status:id,status_code', 'userType'])
            ->where(function ($query) use ($search) {
                $query->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getApproved(int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->user->with(['status:id,status_code', 'userType'])
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getPending(int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->user->with(['status:id,status_code', 'userType'])
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function approve(int $id): bool
    {
        $user = $this->user->withTrashed()->find($id);

        if (!$user) {
            return false;
        }

        if ($user->is_approved) {
            return true;
        }

        return $user->update(['is_approved' => true]);
    }

    public function getStatistics(): array
    {
        return [
            'total_users' => $this->user->count(),
            'approved_users' => $this->user->where('is_approved', true)->count(),
            'pending_users' => $this->user->where('is_approved', false)->count(),
            'users_by_type' => $this->user->selectRaw('type_of_users.type_name, COUNT(*) as count')
                ->join('type_of_users', 'users.type_user_code', '=', 'type_of_users.id')
                ->groupBy('type_of_users.type_name')
                ->pluck('count', 'type_of_users.type_name')
                ->toArray(),
        ];
    }
}
