<?php

namespace App\Gateways;

use App\Notifications\TransferPendingVoucher;
use App\Notifications\TransferRejectedVoucher;
use App\Notifications\TransferVoucherAcceptedAgreement;
use App\Notifications\TransferVoucherAcceptedChilexpress;
use App\Notifications\TransferVoucherAcceptedMixto;
use App\Order;
use App\Payment;
use Illuminate\Http\Response;

class Transfer implements PaymentGateway
{
    protected $callbackData;
    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function getPaymentRequest(Payment $payment, $data)
    {
        $payment->status = Payment::STATUS_PENDING;
        $payment->order->user->notify(new TransferPendingVoucher(['order' => $payment->order]));
        return [
            'public_data' => [
                'amount' => $payment->total,
                'reference' => $data['reference'],
            ]
        ];
    }

    protected function sendApprovedNotification()
    {
        $groupedSales = $this->order->sales->groupBy('is_chilexpress');
        $order = $this->order;

        // All Sales use Chilexpress.
        if (!$groupedSales->has(0)) {
            $order->notify(new TransferVoucherAcceptedChilexpress(['order' => $order]));
            return;
        }

        // No Sale uses Chilexpress.
        if (!$groupedSales->has(1)) {
            $order->notify(new TransferVoucherAcceptedAgreement(['order' => $order]));
            return;
        }

        $order->notify(new TransferVoucherAcceptedMixto(['order' => $order]));
    }

    protected function sendRejectedNotification()
    {
        $order = $this->order;
        $order->notify(new TransferRejectedVoucher(['order' => $order]));
    }

    protected function getPaymentStatus($status)
    {
        switch ($status) {
            case 'approved':
                $this->sendApprovedNotification();
                return Payment::STATUS_SUCCESS;
            default:
                $this->sendRejectedNotification();
                return Payment::STATUS_ERROR;
        }
    }

    public function validateCallbackData($data)
    {
        if (!auth()->user() || !auth()->user()->hasRole('admin')) {
            abort(Response::HTTP_FORBIDDEN);
        }
        if (!array_has($data, 'status') || !array_has($data, 'reference')) {
            abort(Response::HTTP_BAD_REQUEST);
        }
    }

    public function setCallback($data)
    {
        $this->callbackData = $data;
    }

    public function getStatus()
    {
        return $this->getPaymentStatus($this->callbackData['status']);
    }

    public function getReference()
    {
        return $this->callbackData['reference'];
    }

    public function getData()
    {
        return $this->callbackData;
    }
}
