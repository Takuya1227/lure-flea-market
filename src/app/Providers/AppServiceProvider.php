<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(\App\Models\Lure::class, \App\Policies\LurePolicy::class);
        Gate::policy(\App\Models\Transaction::class, \App\Policies\TransactionPolicy::class);
        Gate::policy(\App\Models\BankAccount::class, \App\Policies\BankAccountPolicy::class);
    }
}
