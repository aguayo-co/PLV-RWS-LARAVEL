<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatuses;
use App\Traits\SaveLater;

class Coupon extends Model
{
    use HasStatuses;
    use SaveLater;

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'description',
        'code',
        'valid_from',
        'valid_to',
        'minimum_price',
        'minimum_commission',
        'first_purchase_only',
        'discount_value',
        'discount_type',
        'status',
        'brands_ids',
        'campaigns_ids',
    ];
    protected $with = ['brands', 'campaigns'];
    protected $appends = ['brands_ids', 'campaigns_ids'];

    public function brands()
    {
        return $this->morphedByMany('App\Brand', 'couponable');
    }

    protected function getBrandsIdsAttribute()
    {
        return $this->brands->pluck('id');
    }

    protected function setBrandsIdsAttribute(array $brandsIds)
    {
        if ($this->saveLater('brands_ids', $brandsIds)) {
            return;
        }
        $this->brands()->sync($brandsIds);
        $this->load('brands');
    }

    public function campaigns()
    {
        return $this->morphedByMany('App\Campaign', 'couponable');
    }

    protected function getCampaignsIdsAttribute()
    {
        return $this->campaigns->pluck('id');
    }

    protected function setCampaignsIdsAttribute(array $campaignsIds)
    {
        if ($this->saveLater('campaigns_ids', $campaignsIds)) {
            return;
        }
        $this->campaigns()->sync($campaignsIds);
        $this->load('campaigns');
    }
}
