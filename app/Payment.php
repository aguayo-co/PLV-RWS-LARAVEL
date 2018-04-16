<?php

namespace App;

use App\Traits\HasSingleFile;
use App\Traits\HasStatuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class Payment extends Model
{
    use HasStatuses;
    use HasSingleFile;

    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_SUCCESS = 10;
    const STATUS_ERROR = 98;
    const STATUS_CANCELED = 99;

    protected $fillable = ['order_id', 'status'];
    protected $hidden = ['request'];
    protected $appends = ['request_data', 'transfer_receipt'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($payment) {
            // This field does not really needs to be unique absolutely.
            // Will be used with the `id` field to make a unique combination.
            // This is needed to reduce the possibility of duplicate transaction
            // ids for the payment gateways.
            // Duplicate transaction ids might be common in test mode where tables
            // are reset and an `id` gets reused.
            $payment->uniqid = uniqid();
        });
    }

    /**
     * Get the order to which this payment applies.
     */
    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    public function getTotalAttribute()
    {
        return $this->order->due;
    }

    public function setRequestAttribute($value)
    {
        $this->attributes['request'] = json_encode($value);
    }

    public function getRequestAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getRequestDataAttribute($value)
    {
        return data_get($this, 'request.public_data');
    }

    public function setAttemptsAttribute($value)
    {
        $this->attributes['attempts'] = json_encode($value);
    }

    public function getAttemptsAttribute($value)
    {
        return json_decode($value, true);
    }

    protected function getTransferReceiptAttribute()
    {
        return $this->getFileUrl('transfer_receipt');
    }

    protected function setTransferReceiptAttribute(?UploadedFile $transferReceipt)
    {
        $this->setFile('transfer_receipt', $transferReceipt);
    }
}
