<?php

namespace App\Console\Commands;

use App\SaleReturn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessOldSaleReturns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sale-returns:old-to-canceled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark SaleReturns that have not been shipped as canceled.';

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
        $createdBefore = now()->subDays(config('prilov.sale_returns.days_created_to_canceled'));

        $returns = SaleReturn::where('status', SaleReturn::STATUS_PENDING)
            ->where('updated_at', '<', $createdBefore)->get();

        // We want to fire events.
        foreach ($returns as $return) {
            $return->status = SaleReturn::STATUS_CANCELED;
            $return->save();
        }
    }
}
