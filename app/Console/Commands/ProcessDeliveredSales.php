<?php

namespace App\Console\Commands;

use App\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessDeliveredSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:delivered-to-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark Sales that have been delivered X days ago, as completed.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sales = Sale::where(function ($query) {
            $dateBefore = now()->subDays(config('prilov.sales.days_delivered_to_completed'));

            $deliveredStatus = Sale::STATUS_DELIVERED;
            $deliveredJsonPath = "`status_history`->'$.\"{$deliveredStatus}\".\"date\".\"date\"'";
            $deliveredDate = DB::raw("CAST(JSON_UNQUOTE({$deliveredJsonPath}) as DATETIME)");

            $receivedStatus = Sale::STATUS_RECEIVED;
            $receivedJsonPath = "`status_history`->'$.\"{$receivedStatus}\".\"date\".\"date\"'";
            $receivedDate = DB::raw("CAST(JSON_UNQUOTE({$receivedJsonPath}) as DATETIME)");

            $query->where($deliveredDate, '<', $dateBefore)->orWhere($receivedDate, '<', $dateBefore);
        })->whereBetween('status', [Sale::STATUS_DELIVERED, Sale::STATUS_RECEIVED])->get();

        // We want to fire events.
        foreach ($sales as $sale) {
            $sale->status = Sale::STATUS_COMPLETED;
            $sale->save();
        }
    }
}
