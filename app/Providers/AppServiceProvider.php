<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\BookingCommission;
use App\Models\BookingPayment;
use App\Models\ProviderBranch;
use App\Models\ProviderFile;
use App\Models\ProviderService;
use App\Models\ReceiveRevenueMethod;
use App\Models\ServiceCommission;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\Trip;
use App\Models\TripReservation;
use App\Models\Vehicle;
use App\Models\Notification;
use App\Policies\ProviderOwnedPolicy;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Repositories\TripRepository;
use App\Repositories\Contracts\TypeUserRepositoryInterface;
use App\Repositories\TypeUserRepository;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\BookingRepository;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\TransactionRepository;
use App\Repositories\Contracts\PassengerRepositoryInterface;
use App\Repositories\PassengerRepository;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\TicketRepository;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Repositories\ExchangeRateRepository;
use App\Repositories\Contracts\DigitalPocketRepositoryInterface;
use App\Repositories\DigitalPocketRepository;
use App\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Repositories\CurrencyRepository;
use App\Repositories\Contracts\ReceiveRevenueRepositoryInterface;
use App\Repositories\ReceiveRevenueRepository;
use App\Repositories\Contracts\ProviderSettlementRepositoryInterface;
use App\Repositories\ProviderSettlementRepository;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\CouponRepository;
use App\Repositories\Contracts\CouponStatusRepositoryInterface;
use App\Repositories\CouponStatusRepository;
use App\Repositories\Contracts\DirectTransferPaymentRepositoryInterface;
use App\Repositories\DirectTransferPaymentRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Observers\ProviderBranchObserver;
use App\Policies\ProviderBranchPolicy;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TripRepositoryInterface::class, TripRepository::class);
        $this->app->bind(TypeUserRepositoryInterface::class, TypeUserRepository::class);
        $this->app->bind(BookingRepositoryInterface::class,BookingRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class,TransactionRepository::class);
        $this->app->bind(PassengerRepositoryInterface::class,PassengerRepository::class);
        $this->app->bind(TicketRepositoryInterface::class,TicketRepository::class);
        $this->app->bind(PaymentMethodRepositoryInterface::class,PaymentMethodRepository::class);
        $this->app->bind(ExchangeRateRepositoryInterface::class,ExchangeRateRepository::class);
        $this->app->bind(DigitalPocketRepositoryInterface::class,DigitalPocketRepository::class);
        $this->app->bind(CurrencyRepositoryInterface::class,CurrencyRepository::class);
        $this->app->bind(ReceiveRevenueRepositoryInterface::class,ReceiveRevenueRepository::class);
        $this->app->bind(ProviderSettlementRepositoryInterface::class,ProviderSettlementRepository::class);
        $this->app->bind(CouponRepositoryInterface::class,CouponRepository::class);
        $this->app->bind(CouponStatusRepositoryInterface::class,CouponStatusRepository::class);
        $this->app->bind(DirectTransferPaymentRepositoryInterface::class,DirectTransferPaymentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Trip::class, ProviderOwnedPolicy::class);
        Gate::policy(Booking::class, ProviderOwnedPolicy::class);
        Gate::policy(BookingPayment::class, ProviderOwnedPolicy::class);
        Gate::policy(TripReservation::class, ProviderOwnedPolicy::class);
        Gate::policy(Ticket::class, ProviderOwnedPolicy::class);
        Gate::policy(Vehicle::class, ProviderOwnedPolicy::class);
        Gate::policy(ProviderService::class, ProviderOwnedPolicy::class);
        Gate::policy(ProviderFile::class, ProviderOwnedPolicy::class);
        Gate::policy(ServiceCommission::class, ProviderOwnedPolicy::class);
        Gate::policy(BookingCommission::class, ProviderOwnedPolicy::class);
        Gate::policy(ReceiveRevenueMethod::class, ProviderOwnedPolicy::class);
        Gate::policy(ProviderBranch::class, ProviderBranchPolicy::class);
        ProviderBranch::observe(ProviderBranchObserver::class);

        View::composer('*', function ($view) {
            $settings = Setting::all()->pluck('value', 'key');
            $view->with('siteName', $settings['site_title'] ?? 'Aracari');
            $view->with('siteSettings', $settings);
            $view->with('siteLogo', $settings['site_logo'] ?? null);
        });

        View::composer('*', function ($view) {
            $notifications = collect();
            $notificationsCount = 0;

            if (auth()->check()) {
                $notifications = Notification::where('notifiable_id', auth()->id())
                    ->where('notifiable_type', get_class(auth()->user()))
                    ->whereNull('read_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

                    
                $notificationsCount = $notifications->count();
            }

            $view->with([
                'notifications' => $notifications,
                'notificationsCount' => $notificationsCount,
            ]);
        });
    }
}