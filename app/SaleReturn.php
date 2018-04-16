<?php

namespace App;

use App\Events\SaleReturnSaved;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SaveLater;
use App\Traits\HasStatuses;
use App\Traits\HasStatusHistory;

class SaleReturn extends Model
{
    use SaveLater;
    use HasStatuses;
    use HasStatusHistory;

    const STATUS_PENDING = 0;
    const STATUS_SHIPPED = 40;
    const STATUS_DELIVERED = 41;
    const STATUS_RECEIVED = 49;
    const STATUS_COMPLETED = 90;
    const STATUS_CANCELED = 99;

    protected $fillable = ['status', 'products_ids'];

    protected $dispatchesEvents = [
        'saved' => SaleReturnSaved::class,
    ];

    public function products()
    {
        return $this->belongsToMany('App\Product', 'product_sale');
    }

    public function sales()
    {
        return $this->belongsToMany('App\Sale', 'product_sale');
    }

    protected function getSaleAttribute()
    {
        return $this->sales->first();
    }

    protected function getOwnersIdsAttribute()
    {
        return collect([$this->sale->user_id, $this->sale->order->user_id]);
    }

    protected function getProductsIdsAttribute()
    {
        return $this->sale ? $this->sale->returned_products_ids : [];
    }

    /**
     * Associate the products to be returned with this SaleReturn.
     * It is done on the same table as products get associated to sales.
     * A third column tells was if it is being returned or not.
     *
     * @param  array $data An array with two keys: sale_id and products_ids.
     */
    protected function setProductsIdsAttribute(array $data)
    {
        if ($this->saveLater('products_ids', $data)) {
            return;
        }

        $productsIdsSync = [];
        $sale = Sale::find($data['sale_id']);

        foreach ($sale->products_ids as $productId) {
            if (in_array($productId, $data['products_ids'])) {
                $productsIdsSync[$productId] = ['sale_return_id' => $this->id];
                continue;
            }
            $productsIdsSync[$productId] = ['sale_return_id' => null];
        }

        $sale->products()->sync($productsIdsSync);
        $sale->load('products');
    }
}
