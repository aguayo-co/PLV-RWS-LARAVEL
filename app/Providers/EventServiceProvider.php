<?php

namespace App\Providers;

use App\Observers\SaleObserver;
use App\Sale;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\PaymentSuccessful' => [
            'App\Listeners\ApproveOrder',
        ],
        'App\Events\SaleReturnSaved' => [
            'App\Listeners\ProcessSaleReturnCredits',
            'App\Listeners\ProcessSaleStatusFromReturn',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        Sale::observe(SaleObserver::class);
    }
}
