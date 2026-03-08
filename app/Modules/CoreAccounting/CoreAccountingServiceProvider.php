<?php

namespace App\Modules\CoreAccounting;

use App\Modules\CoreAccounting\Application\FinancialEventDispatcher;
use Illuminate\Support\ServiceProvider;

class CoreAccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FinancialEventDispatcher::class, function ($app) {
            return new FinancialEventDispatcher($app);
        });
    }

    public function boot(): void
    {
        //
    }
}

