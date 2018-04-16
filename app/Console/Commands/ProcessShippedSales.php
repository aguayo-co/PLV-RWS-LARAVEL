<?php

namespace App\Console\Commands;

use App\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessShippedSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:shipped-to-delivered';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark Sales that have been shipped X days ago, as delivered.';

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
        $shippedBefore = now()->subDays(config('prilov.sales.days_shipping_to_delivered'));

        $status = Sale::STATUS_SHIPPED;
        $ShippedJsonPath = "`status_history`->'$.\"{$status}\".\"date\".\"date\"'";
        $shippedDate = DB::raw("CAST(JSON_UNQUOTE({$ShippedJsonPath}) as DATETIME)");

        $sales = Sale::where('status', Sale::STATUS_SHIPPED)->where($shippedDate, '<', $shippedBefore)->get();

        // We want to fire events.
        foreach ($sales as $sale) {
            $sale->status = Sale::STATUS_DELIVERED;
            $sale->save();
        }
    }
}
