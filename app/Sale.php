<?php

namespace App;

use App\Events\SaleSaved;
use App\Traits\HasStatuses;
use App\Traits\HasStatusHistory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasStatuses;
    use HasStatusHistory;

    // Numbers are used to know when an action can be taken.
    // For instance, an order can not be marked as shipped if it
    // has not been payed: "status < PAYED".
    // But once it passes that stage, can be marked as shipped at any time.
    const STATUS_SHOPPING_CART = 10;
    const STATUS_PAYMENT = 20;
    const STATUS_PAYED = 30;
    const STATUS_SHIPPED = 40;
    const STATUS_DELIVERED = 41;
    const STATUS_RECEIVED = 49;
    const STATUS_COMPLETED = 90;
    const STATUS_COMPLETED_RETURNED = 91;
    const STATUS_COMPLETED_PARTIAL = 92;
    const STATUS_CANCELED = 99;

    protected $fillable = ['shipment_details', 'status'];
    protected $with = ['products', 'shippingMethod', 'creditsTransactions', 'returns'];
    protected $appends = ['returned_products_ids', 'total', 'commission'];

    protected $dispatchesEvents = [
        'saved' => SaleSaved::class,
    ];

    /**
     * Get the user that sells this.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    /**
     * Get the sale products.
     */
    public function products()
    {
        return $this->belongsToMany('App\Product')->withPivot('sale_return_id');
    }

    public function getProductsIdsAttribute()
    {
        return $this->products->pluck('id');
    }

    /**
     * Get the sale products that were marked for return.
     */
    public function getReturnedProductsAttribute()
    {
        return $this->products->whereNotIn('pivot.sale_return_id', [null]);
    }

    public function getReturnedProductsIdsAttribute()
    {
        return $this->returned_products->pluck('id');
    }

    /**
     * The total value of the sale.
     */
    public function getTotalAttribute()
    {
        return $this->products->sum('price');
    }

    /**
     * The total value of the returned products.
     */
    public function getReturnedTotalAttribute()
    {
        return $this->products->whereIn('id', $this->returnedProductsIds)->sum('price');
    }

    /**
     * The total value of the order.
     */
    public function getCommissionAttribute()
    {
        return $this->products->sum(function ($product) {
            return round($product->price * $product->commission / 100);
        });
    }

    public function getReturnedCommissionAttribute()
    {
        return $this->products->whereIn('id', $this->returnedProductsIds)->sum(function ($product) {
            return round($product->price * $product->commission / 100);
        });
    }

    public function returns()
    {
        return $this->belongsToMany('App\SaleReturn', 'product_sale');
    }

    public function creditsTransactions()
    {
        return $this->hasMany('App\CreditsTransaction');
    }


    public function shippingMethod()
    {
        return $this->belongsTo('App\ShippingMethod');
    }

    public function setShipmentDetailsAttribute($value)
    {
        $this->attributes['shipment_details'] = json_encode($value);
    }

    public function getShipmentDetailsAttribute($value)
    {
        return json_decode($value, true);
    }
}
