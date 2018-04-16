<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditsTransaction extends Model
{

    protected $fillable = ['user_id', 'amount', 'sale_id', 'order_id', 'extra'];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }

    public function setExtraAttribute($value)
    {
        $this->attributes['extra'] = json_encode($value);
    }

    public function getExtraAttribute($value)
    {
        return json_decode($value, true);
    }
}
