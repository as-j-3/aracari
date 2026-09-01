<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            if (!Schema::hasTable('settings')) {
                $view->with('systemSettings', collect());
                return;
            }

            $settings = Cache::rememberForever('system_settings', function () {
                return Setting::all()->pluck('value', 'key');
            });
            $view->with('systemSettings', $settings);
        });
    }
}
