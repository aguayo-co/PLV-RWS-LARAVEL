<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Route::model('address', \App\Address::class);
        Route::model('banner', \App\Banner::class);
        Route::model('brand', \App\Brand::class);
        Route::model('campaign', \App\Campaign::class);
        Route::model('category', \App\Category::class);
        Route::model('subcategory', \App\Category::class);
        Route::model('color', \App\Color::class);
        Route::model('condition', \App\Condition::class);
        Route::model('coupon', \App\Coupon::class);
        Route::model('credits_transaction', \App\CreditsTransaction::class);
        Route::model('group', \App\Group::class);
        Route::model('menu_item', \App\MenuItem::class);
        Route::model('menu', \App\Menu::class);
        Route::model('message', \Cmgmyr\Messenger\Models\Message::class);
        Route::model('order', \App\Order::class);
        Route::model('product', \App\Product::class);
        Route::model('sale', \App\Sale::class);
        Route::model('sale_return', \App\SaleReturn::class);
        Route::model('shipping_method', \App\ShippingMethod::class);
        Route::model('size', \App\Size::class);
        Route::model('slider', \App\Slider::class);
        Route::model('thread', \App\Thread::class);
        Route::model('user', \App\User::class);

        // A Rating does not exist on first access.
        // Create a rating for the given SaleId if one does not exist.
        Route::bind('rating', function ($saleId) {
            $rating =  \App\Rating::where('sale_id', $saleId)->first();
            if ($rating) {
                return $rating;
            }

            \App\Sale::where('id', $saleId)->setEagerLoads([])->select('id')->firstOrFail();

            $rating = new \App\Rating();
            $rating->sale_id = $saleId;
            $rating->status = \App\Rating::STATUS_UNPUBLISHED;
            $rating->save();
            return $rating->fresh();
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapCallbackRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "callback" routes for the application.
     *
     * @return void
     */
    protected function mapCallbackRoutes()
    {
        Route::prefix('callback')
             ->middleware('callback')
             ->namespace($this->namespace)
             ->group(base_path('routes/callback.php'));
    }
}
