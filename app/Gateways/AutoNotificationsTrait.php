<?php

namespace App\Gateways;

use App\Notifications\AutoConfirmedAgreement;
use App\Notifications\AutoConfirmedChilexpress;

trait AutoNotificationsTrait
{
    public function sendApprovedNotification()
    {
        $groupedSales = $this->order->sales->groupBy('is_chilexpress');
        $order = $this->order;

        // All Sales use Chilexpress.
        if (!$groupedSales->has(0)) {
            $order->notify(new AutoConfirmedChilexpress(['order' => $order]));
            return;
        }

        // No Sale uses Chilexpress.
        if (!$groupedSales->has(1)) {
            $order->notify(new AutoConfirmedAgreement(['order' => $order]));
            return;
        }

        // $order->notify(new AutoConfirmedMixto(['order' => $order]));
    }

    public function sendRejectedNotification()
    {
        // No notifications for Auto rejected payments.
    }
}
