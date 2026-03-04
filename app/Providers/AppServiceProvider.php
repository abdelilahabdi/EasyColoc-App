<?php

namespace App\Providers;

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\Invitation;
use App\Models\Payment;
use App\Models\Settlement;
use App\Policies\ColocationPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InvitationPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\SettlementPolicy;
use App\Services\BalanceService;
use App\Services\ColocationService;
use App\Services\InvitationService;
use App\Services\PaymentService;
use App\Services\ReputationService;
use App\Services\SettlementService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register BalanceService as singleton for performance
        $this->app->singleton(BalanceService::class, function ($app) {
            return new BalanceService();
        });

        // Register ColocationService
        $this->app->singleton(ColocationService::class, function ($app) {
            return new ColocationService();
        });

        // Register SettlementService with BalanceService dependency
        $this->app->singleton(SettlementService::class, function ($app) {
            return new SettlementService($app->make(BalanceService::class));
        });

        // Register ReputationService
        $this->app->singleton(ReputationService::class, function ($app) {
            return new ReputationService();
        });

        // Register InvitationService with ColocationService dependency
        $this->app->singleton(InvitationService::class, function ($app) {
            return new InvitationService($app->make(ColocationService::class));
        });

        // Register PaymentService with BalanceService dependency
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService($app->make(BalanceService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share user colocations with all views
        view()->composer('*', function ($view) {
            if (auth()->check()) {
                $userColocations = auth()->user()->colocations()
                    ->with('users')
                    ->orderBy('status', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($colocation) {
                        $membership = $colocation->users->where('id', auth()->id())->first();
                        $colocation->user_role = $membership ? $membership->pivot->role : 'member';
                        $colocation->user_left_at = $membership ? $membership->pivot->left_at : null;
                        return $colocation;
                    });
                
                $view->with('userColocations', $userColocations);
            }
        });
    }

    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Colocation::class => ColocationPolicy::class,
        Expense::class => ExpensePolicy::class,
        Invitation::class => InvitationPolicy::class,
        Payment::class => PaymentPolicy::class,
        Settlement::class => SettlementPolicy::class,
    ];
}
