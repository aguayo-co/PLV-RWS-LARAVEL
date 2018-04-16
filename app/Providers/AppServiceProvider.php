<?php

namespace App\Providers;

use App\Notifications\Messages\UserMailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariables)
     */
    public function boot()
    {
        # MySQL < 5.7 do not allow longer strings.
        # https://laravel.com/docs/5.6/migrations#creating-indexes
        Schema::defaultStringLength(191);

        # Change the default rendering method for ResetPassword.
        ResetPasswordNotification::$toMailCallback = function ($notifiable, $token) {
            return (new UserMailMessage($notifiable))->view(
                'email.token',
                ['token' => $token]
            );
        };

        # Each template can know the view that was called.
        View::composer('*', function ($view) {
            $view->with('view_name', $view->getName());
        });

        Validator::extend('empty_with', 'App\Validators\EmptyWithValidator@validateEmptyWith');
        Validator::replacer('empty_with', 'App\Validators\EmptyWithValidator@replaceEmptyWith');

        DB::listen(function ($query) {
            Log::debug($query->sql);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
