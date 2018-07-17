<?php

use Illuminate\Http\Request;

/**
 * Callback routes
 *
 * Routes performed by other services as callbacks.
 */

Route::name('callback.')->group(function () {
    Route::match(['get', 'post'], '/gateway/{gateway}', 'PaymentController@gatewayCallback')->name('gateway')
        ->where('gateway', SLUG_REGEX);
});
