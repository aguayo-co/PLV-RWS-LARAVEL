<?php

namespace App\Console\Commands;

use App\CreditsTransaction;
use App\Notifications\PurchaseCanceled;
use App\Order;
use App\Payment;
use App\Product;
use App\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PendingToCanceled extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:pending-to-canceled {payment?* : The ID of the payment to cancel.}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $days = config('prilov.payments.minutes_until_canceled');
        $this->description = "Mark payments (pending or rejected) as canceled.\n";
        $this->description .= "If non are given, cancel payments that have not been updated in {$days} minutes";
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $payments = $this->getPayments();
        // We want to fire events.
        foreach ($payments as $payment) {
            DB::transaction(function () use ($payment) {
                $payment->status = Payment::STATUS_CANCELED;
                $payment->save();
                $this->cancelOrder($payment);
                $this->cancelSales($payment);
                $this->publishProducts($payment);
            });
            $this->sendNotifications($payment);
        }
    }

    protected function getPayments()
    {
        $query = Payment::whereIn('status', [Payment::STATUS_ERROR, Payment::STATUS_PENDING]);

        $paymentIds = $this->argument('payment');
        if ($paymentIds) {
            return $query->where('id', $paymentIds)->get();
        }

        $paymentBefore = now()->subMinutes(config('prilov.payments.minutes_until_canceled'));
        return $query->where('updated_at', '<', $paymentBefore)->get();
    }

    protected function cancelOrder($payment)
    {
        $payment->order->status = Order::STATUS_CANCELED;
        $payment->order->save();

        $usedCredits = $payment->order->used_credits;

        if ($usedCredits) {
            CreditsTransaction::create([
                'user_id' => $payment->order->user_id,
                'amount' => $usedCredits,
                'order_id' => $payment->order->id,
                'extra' => ['reason' => __('prilov.credits.reasons.orderCanceled')]
            ]);
        }
    }

    protected function sendNotifications($payment)
    {
        if ($payment->gateway === 'Transfer') {
            $payment->order->user->notify(new PurchaseCanceled(['order' => $payment->order]));
        }
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
