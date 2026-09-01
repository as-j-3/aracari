<?php

namespace App\Providers;

use App\Models\Vehicle;
use App\Models\ProviderBranch;
use App\Policies\VehiclePolicy;
use App\Policies\ProviderBranchPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 *  AuthServiceProvider for policy registration
 * 
 * Centralizes authorization logic and policy mappings.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Vehicle::class => VehiclePolicy::class,
        ProviderBranch::class => ProviderBranchPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define gates for common authorization checks
        \Gate::define('manage-vehicle', function ($user, $vehicle) {
            return $user->isAdmin() || $vehicle->user_id === $user->id;
        });

        \Gate::define('admin-only', function ($user) {
            return $user->isAdmin();
        });

        \Gate::define('provider-only', function ($user) {
            return $user->isProvider();
        });

        \Gate::define('customer-only', function ($user) {
            return $user->isCustomer();
        });
    }
}
