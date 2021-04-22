<?php

use Illuminate\Http\Request;

/**
 * Api routes
 *
 * Global auth middleware is managed here, but be sure to check
 * permissions and other middleware added on the controllers directly.
 */

include_once 'helpers.php';

Route::name('api.')->group(function () {

    # User account management and password reset..
    Route::middleware('guest')->group(function () {
        Route::post('users', 'Auth\UserController@store')->name('register');
        Route::post('users/login', 'Auth\LoginController@login')->name('login');

        Route::get('users/password/recovery/{email}', 'Auth\ForgotPasswordController@sendResetLinkEmail')
            ->name('password.recovery.email');
        Route::post('users/password/recovery/{email}', 'Auth\ForgotPasswordController@validateResetToken')
            ->name('password.recovery.token');
        Route::post('users/password/reset/{email}', 'Auth\ResetPasswordController@reset')
            ->name('password.reset');
    });

    # Public User routes
    Route::get('users', 'Auth\UserController@index')->name('users');
    Route::get('users/{user_scoped}', 'Auth\UserController@show')->name('user.get')->where('user_scoped', ID_REGEX);

    Route::get('/configs', 'ConfigController@index')->name('config');

    Route::get('regions', 'AddressController@regions')->name('regions');

    create_crud_routes('Banner', SLUG_REGEX);
    create_crud_routes('Brand', SLUG_REGEX);
    create_crud_routes('Campaign', SLUG_REGEX);
    create_crud_routes('Category', SLUG_REGEX);
    // An extra route for subcategories.
    Route::get('categories/{category}/{subcategory}', 'CategoryController@showSubcategory')
        ->name('subcategory.get')->where(['category' => SLUG_REGEX, 'subcategory' => SLUG_REGEX]);
    create_crud_routes('Color', SLUG_REGEX);
    create_crud_routes('Condition', SLUG_REGEX);
    create_crud_routes('Group', SLUG_REGEX);
    create_crud_routes('Menu', SLUG_REGEX);
    create_crud_routes('MenuItem', ID_REGEX);
    create_crud_routes('ShippingMethod', SLUG_REGEX);
    create_crud_routes('Size', ID_REGEX);
    create_crud_routes('Slider', SLUG_REGEX);

    create_protected_rud_routes('Order', ID_REGEX);
    create_protected_rud_routes('Sale', ID_REGEX);

    create_protected_crud_routes('Coupon', ID_REGEX);
    create_protected_crud_routes('CreditsTransaction', ID_REGEX);
    create_protected_crud_routes('Payroll', ID_REGEX);
    create_protected_crud_routes('SaleReturn', ID_REGEX);

    # Public Product routes
    Route::get('products', 'ProductController@index')->name('products');
    Route::get('products/{product}', 'ProductController@show')->name('product.get')->where('product', ID_REGEX);

    Route::prefix('threads')->group(base_path('routes/api/threads.php'));
    Route::prefix('ratings')->group(base_path('routes/api/ratings.php'));
    Route::prefix('rating_archives')->group(base_path('routes/api/rating_archives.php'));

    # Auth routes.
    # Only authenticated requests here.
    Route::middleware('auth:api')->group(function () {
        # Routes for user account and profile administration.
        Route::patch('users/{user}', 'Auth\UserController@update')
            ->name('user.update')->where('user', ID_REGEX);
        Route::delete('users/{user_scoped}', 'Auth\UserController@delete')
            ->name('user.delete')->where('user_scoped', ID_REGEX);

        Route::get('users/{user}/addresses', 'AddressController@index')
            ->name('user.addresses.get')->where('user', ID_REGEX);
        Route::post('users/{user}/addresses', 'AddressController@store')
            ->name('user.address.create')->where('user', ID_REGEX);
        Route::get('users/{user}/addresses/{address}', 'AddressController@show')
            ->name('user.address.get')->where(['user' => ID_REGEX, 'address' => ID_REGEX]);
        Route::patch('users/{user}/addresses/{address}', 'AddressController@update')
            ->name('user.address.update')->where(['user' => ID_REGEX, 'address' => ID_REGEX]);
        Route::delete('users/{user}/addresses/{address}', 'AddressController@ownerDelete')
            ->name('user.address.delete')->where(['user' => ID_REGEX, 'address' => ID_REGEX]);

        # Routes for Product administration.
        Route::post('products', 'ProductController@store')->name('product.create');
        Route::patch('products/{product}', 'ProductController@update')
            ->name('product.update')->where('product', ID_REGEX);
        Route::post('products/{product}/replicate', 'ProductController@replicate')
            ->name('product.replicate')->where('product', ID_REGEX);
        Route::delete('products/{product}', 'ProductController@ownerDelete')
            ->name('product.delete')->where('product', ID_REGEX);

        # Routes for shopping cart.
        Route::get('/shopping_cart', 'OrderController@getShoppingCart')->name('shopping_cart');
        Route::patch('/shopping_cart', 'OrderController@updateShoppingCart')->name('shopping_cart.update');

        # Routes for Payments.
        Route::get('payments', 'PaymentController@index')->name('payments');
        Route::get('payments/{payment}', 'PaymentController@show')->name('payment')->where('product', ID_REGEX);
        Route::patch('payments/{payment}', 'PaymentController@update')
            ->name('payment.update')->where('product', ID_REGEX);
        Route::post('payments', 'PaymentController@store')->name('payment.create');

        # Alternative routes for payment methods.
        Route::get('/shopping_cart/payment', 'PaymentController@store')->name('shopping_cart.payment.create');
        Route::get('/orders/{order}/payment', 'PaymentController@generatePayment')
            ->name('orders.payment.create')->where('order', ID_REGEX);

        Route::get('/report/first', 'ReportController@showFirst')->name('report.first');
        Route::post('/report/second', 'ReportController@showSecond')->name('report.second');
        Route::post('/report/third', 'ReportController@showThird')->name('report.third');
    });
});
