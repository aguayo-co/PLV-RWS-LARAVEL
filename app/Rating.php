<?php

namespace App;

use App\Notifications\ReceivedRating;
use App\Traits\DateSerializeFormat;
use App\Traits\HasStatuses;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasStatuses;
    use DateSerializeFormat;

    const STATUS_UNPUBLISHED = 0;
    const STATUS_PUBLISHED = 1;

    protected $primaryKey = 'sale_id';
    public $incrementing = false;

    public $fillable = ['seller_rating', 'seller_comment', 'buyer_rating', 'buyer_comment'];


    public static function boot()
    {
        parent::boot();
        self::saved(function ($rating) {
            if (!array_has($rating->getChanges(), 'status')) {
                return;
            }

            // Send notification when status changed and
            // when the seller got rated.
            if ($rating->buyer_rating && $rating->status == Rating::STATUS_PUBLISHED) {
                $rating->sale->user->notify(new ReceivedRating(['rating' => $rating]));
            }
        });
    }

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }

    public function getSellerAttribute()
    {
        return $this->sale->user;
    }

    public function getBuyerAttribute()
    {
        return $this->sale->order->user;
    }

    public function getSellerIdAttribute()
    {
        return $this->sale->user_id;
    }

    public function getBuyerIdAttribute()
    {
        return $this->sale->order->user_id;
    }

    protected function getOwnersIdsAttribute()
    {
        return collect([$this->sale->user_id, $this->sale->order->user_id]);
    }
}
