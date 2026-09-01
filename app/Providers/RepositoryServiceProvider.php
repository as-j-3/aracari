<?php

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\AdRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\AdRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Repository bindings
 * 
 * Binds interfaces to their implementations
 * Follows SOLID principles with dependency injection
 */
class RepositoryServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        //  AdRepositoryInterface to AdRepository
        $this->app->bind(
            AdRepositoryInterface::class,
            AdRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}