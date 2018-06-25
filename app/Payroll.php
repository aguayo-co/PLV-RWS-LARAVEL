<?php

namespace App;

use App\Traits\DateSerializeFormat;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use DateSerializeFormat;

    /**
     * Get the children for the item.
     */
    public function creditsTransactions()
    {
        return $this->hasMany('App\CreditsTransaction');
    }

    protected function getCreditsTransactionsIdsAttribute()
    {
        return $this->credits_transactions->pluck('id');
    }
}
