<?php

namespace App;

use App\Traits\DateSerializeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * A RatingArchive is a rating created with previous
 * rating system where it was not associated to a sale.
 */
class RatingArchive extends Model
{
    use DateSerializeFormat;

    public function seller()
    {
        return $this->belongsTo('App\User', 'seller_id')->withTrashed();
    }

    public function buyer()
    {
        return $this->belongsTo('App\User', 'buyer_id')->withTrashed();
    }
}
