<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Chilexpress\Chilexpress;

class ChilexpressServiceProvider extends ServiceProvider
{
    public $bindings = [
        'chilexpress' => Chilexpress::class,
    ];
}
