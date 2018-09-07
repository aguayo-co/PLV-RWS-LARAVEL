<?php

namespace App\Http\Controllers\Report;

use App\CreditsTransaction;
use App\Traits\CreditsTransactionsSum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait CreditsTransactionsReport
{
    use CreditsTransactionsSum;

    protected function getInitialCredits(Request $request)
    {
        $query = DB::table('credits_transactions')
            ->whereRaw('credits_transactions.created_at < ?', $request->from);
        $this->setActiveCreditsTransactionsConditions($query);
        return $query->sum('credits_transactions.amount');
    }

    protected function getCreditsTransactionsReport(Request $request)
    {
        $query = DB::table('credits_transactions');
        $this->setActiveCreditsTransactionsConditions($query);
        $this->setDateRanges($request, 'credits_transactions.created_at', $query);
        return $query->addSelect(DB::raw('CAST(SUM(credits_transactions.amount) AS SIGNED) credits'))
            ->get();
    }
}
