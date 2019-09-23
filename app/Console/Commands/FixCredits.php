<?php

namespace App\Console\Commands;

use App\Order;
use App\CreditsTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FixCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credits:fix-used-credits';

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
        $broken_orders = [];
        $headers = ['id', 'used_credits', 'missing_payment', 'credit_transactions', 'expected_payment', 'payment'];

        // Payment date.
        $payment_status = Order::STATUS_PAYMENT;
        $paymentJsonPath = "orders.status_history->'$.\"{$payment_status}\".date'";
        $paymentDate = "CAST(JSON_UNQUOTE({$paymentJsonPath}) as DATETIME)";

        $fee_charged_since = new Carbon('2018/07/25 16:47:27 -0500');

        Order::where("status", ">=", Order::STATUS_PAYMENT)
            ->where("status", "<=", Order::STATUS_PAYED)
            ->whereRaw("{$paymentDate} > ?", "2019-02-19 00:00:00")
            ->with('sales', 'user', 'payments', 'coupon', 'creditsTransactions')
            ->chunk(500, function ($orders) use (&$broken_orders, $fee_charged_since) {
                foreach ($orders as $order) {
                    // Only orders that have a payment.
                    if (!$order->active_payment) {
                        continue;
                    }

                    // Include gateway fee.
                    $expected_payment = $order->due;
                    if (in_array($order->active_payment->gateway, ['PayU', 'MercadoPago']) && $order->active_payment->created_at > $fee_charged_since) {
                        $expected_payment = (int) ($expected_payment * 1.05);
                    }

                    // Payment was enough!
                    if ($expected_payment <= $order->active_payment->amount) {
                        continue;
                    }

                    $credits_transactions_count = $order->creditsTransactions->count();

                    $new_transaction = new CreditsTransaction();
                    $new_transaction->fill([
                        'order_id' => $order->id,
                        'user_id' => $order->user->id,
                        'amount' => -($expected_payment - $order->active_payment->amount),
                        'extra' => [
                            'reason' => __('prilov.credits.reasons.orderPayment'),
                            'notes' => 'Créditos faltantes por descontar'
                        ]
                    ]);
                    $new_transaction->created_at = $order->active_payment->created_at;
                    $new_transaction->save();

                    $broken_orders[] = [
                        $order->id,
                        $order->used_credits,
                        $expected_payment - $order->active_payment->amount,
                        $credits_transactions_count,
                        $expected_payment,
                        $order->active_payment->amount
                    ];
                }
            });
        $this->table($headers, $broken_orders);
    }
}
