<?php

namespace App\Providers;

use App\Observers\PaymentObserver;
use App\Observers\SaleObserver;
use App\Observers\SaleReturnObserver;
use App\Payment;
use App\Sale;
use App\SaleReturn;
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
        'App\Events\PaymentStarted' => [
            'App\Listeners\FreezeOrder',
        ],

        'App\Events\PaymentAborted' => [
            'App\Listeners\UnfreezeOrder',
        ],

        'App\Events\OrderReversed' => [
            'App\Listeners\UnfreezeOrder',
        ],

        'App\Events\PaymentSuccessful' => [
            'App\Listeners\ApproveOrder',
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        SaleReturn::observe(SaleReturnObserver::class);
        Sale::observe(SaleObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
