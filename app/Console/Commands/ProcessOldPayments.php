<?php

namespace App\Console\Commands;

use App\Sale;
use App\Order;
use App\Payment;
use App\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessOldPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:pending-to-canceled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark old payments (pending or rejected) as canceled.';

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
        $paymentBefore = now()->subMinutes(config('prilov.payments.minutes_until_canceled'));

        $payments = Payment::whereIn('status', [Payment::STATUS_ERROR, Payment::STATUS_PENDING])
            ->where('updated_at', '<', $paymentBefore)->get();

        // We want to fire events.
        foreach ($payments as $payment) {
            DB::transaction(function () use ($payment) {
                $payment->status = Payment::STATUS_CANCELED;
                $payment->save();
                $this->cancelOrder($payment);
                $this->cancelSales($payment);
                $this->publishProducts($payment);
            });
        }
    }

    protected function cancelOrder($payment)
    {
        $payment->order->status = Order::STATUS_CANCELED;
        $payment->order->save();
    }

    protected function cancelSales($payment)
    {
        foreach ($payment->order->sales as $sale) {
            $sale->status = Sale::STATUS_CANCELED;
            $sale->save();
        }
    }

    protected function publishProducts($payment)
    {
        foreach ($payment->order->products as $product) {
            $product->status = Product::STATUS_AVAILABLE;
            $product->save();
        }
    }
}
