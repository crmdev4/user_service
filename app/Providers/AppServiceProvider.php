<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        Passport::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::enablePasswordGrant();
        
        // if ($this->app->environment('production')) {
        \URL::forceScheme('https');
        // }

        Passport::tokensCan([
            'crm_company' => 'crm company',
            'crm_user' => 'crm user',
        ]);
    }
}
