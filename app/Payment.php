<?php

namespace App;

use App\Traits\DateSerializeFormat;
use App\Traits\HasSingleFile;
use App\Traits\HasStatuses;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasStatuses;
    use HasSingleFile;
    use DateSerializeFormat;

    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_SUCCESS = 10;
    const STATUS_ERROR = 98;
    const STATUS_CANCELED = 99;

    protected $fillable = ['order_id', 'status'];
    protected $hidden = ['request', 'cloudFiles'];
    protected $with = ['cloudFiles'];
    protected $appends = ['request_data', 'transfer_receipt', 'cancel_by'];

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        if (!$this->uniqid) {
            // This is needed to reduce the possibility of duplicate transaction
            // ids for the payment gateways.
            // Duplicate transaction ids might be common in test mode where tables
            // are reset and an `id` gets reused.
            $this->uniqid = uniqid();
        }
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

    protected function setTransferReceiptAttribute($transferReceipt)
    {
        $this->setFile('transfer_receipt', $transferReceipt);
    }

    protected function getCancelByAttribute()
    {
        if (!in_array($this->status, [Payment::STATUS_ERROR, Payment::STATUS_PENDING])) {
            return null;
        }
        return $this->updated_at->addMinutes(config('prilov.payments.minutes_until_canceled'));
    }
}
