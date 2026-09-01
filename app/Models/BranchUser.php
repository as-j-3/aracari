<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchUser extends Model
{
    use HasFactory;

    protected $table = 'branch_users';

    protected $fillable = [
        'user_id',
        'branch_id',
    ];

    // Accessors للحصول على دور المستخدم من نظام UserRole/Role
    public function getRoleAttribute(): ?string
    {
        return $this->user?->userRole?->name ?? null;
    }

    public function hasRole(string $role): bool
    {
        return $this->user?->userRole?->name === $role;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->user?->hasPermission($permission) ?? false;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(ProviderBranch::class, 'branch_id');
    }
}
