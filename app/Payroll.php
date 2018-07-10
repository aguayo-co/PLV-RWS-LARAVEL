<?php

namespace App;

use App\Traits\DateSerializeFormat;
use App\Traits\SaveLater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Payroll extends Model
{
    use DateSerializeFormat;
    use SaveLater;

    protected $fillable = ['credits_transactions_ids'];

    /**
     * Get the children for the item.
     */
    public function creditsTransactions()
    {
        return $this->hasMany('App\CreditsTransaction');
    }

    protected function getCreditsTransactionsIdsAttribute()
    {
        return $this->creditsTransactions->pluck('id');
    }

    protected function setCreditsTransactionsIdsAttribute(array $ids)
    {
        if ($this->saveLater('credits_transactions_ids', $ids)) {
            return;
        }
        $payroll = $this;
        $this->creditsTransactions()->whereNotIn('id', $ids)->get()->each(function ($transaction) use ($payroll) {
            $transaction->payroll()->associate($payroll);
            $transaction->save();
        });
        CreditsTransaction::whereIn('id', $ids)->get()->each(function ($transaction) use ($payroll) {
            $transaction->payroll()->associate($payroll);
            $transaction->save();
        });
        $this->load('creditsTransactions');
    }

    protected function getDownloadUrlsAttribute()
    {
        $pending = URL::temporarySignedRoute(
            'downloads.payroll',
            now()->addMinutes(30),
            ['payroll' => $this->id]
        );
        $complete = URL::temporarySignedRoute(
            'downloads.payroll',
            now()->addMinutes(30),
            ['payroll' => $this->id, 'complete' => 'complete']
        );
        return [$pending, $complete];
    }
}
