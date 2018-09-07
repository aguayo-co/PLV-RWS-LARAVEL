<?php

namespace App;

use App\Traits\DateSerializeFormat;
use App\Traits\HasStatuses;
use Illuminate\Database\Eloquent\Model;

class CreditsTransaction extends Model
{
    use HasStatuses;
    use DateSerializeFormat;

    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_REJECTED = 99;

    protected $fillable = ['user_id', 'amount', 'commission', 'sale_id', 'order_id', 'extra', 'transfer_status'];
    protected $casts = [
        'extra' => 'array',
    ];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo('App\User')->withTrashed();
    }

    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    public function sale()
    {
        return $this->belongsTo('App\Sale');
    }

    public function payroll()
    {
        return $this->belongsTo('App\Payroll');
    }
}
