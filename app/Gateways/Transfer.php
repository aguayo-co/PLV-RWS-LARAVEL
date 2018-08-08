<?php

namespace App\Gateways;

use App\Notifications\TransferPendingVoucher;
use App\Notifications\TransferRejectedVoucher;
use App\Notifications\TransferVoucherAcceptedAgreement;
use App\Notifications\TransferVoucherAcceptedChilexpress;
use App\Notifications\TransferVoucherAcceptedMixto;
use App\Payment;
use Illuminate\Http\Response;

class Transfer implements PaymentGateway
{
    protected $callbackData;
    protected $payment;

    public function getPaymentRequest($data)
    {
        $this->payment->order->user->notify(new TransferPendingVoucher(['order' => $this->payment->order]));
        return [
            'public_data' => [
                'amount' => $this->payment->total,
                'reference' => $data['reference'],
            ]
        ];
    }

    public function sendApprovedNotification()
    {
        $groupedSales = $this->payment->order->sales->groupBy('is_chilexpress');
        $order = $this->payment->order;

        // All Sales use Chilexpress.
        if (!$groupedSales->has(0)) {
            $order->user->notify(new TransferVoucherAcceptedChilexpress(['order' => $order]));
            return;
        }

        // No Sale uses Chilexpress.
        if (!$groupedSales->has(1)) {
            $order->user->notify(new TransferVoucherAcceptedAgreement(['order' => $order]));
            return;
        }

        $order->user->notify(new TransferVoucherAcceptedMixto(['order' => $order]));
    }

    public function sendRejectedNotification()
    {
        $order = $this->payment->order;
        $order->user->notify(new TransferRejectedVoucher(['order' => $order]));
    }

    protected function getPaymentStatus($status)
    {
        switch ($status) {
            case 'approved':
                return Payment::STATUS_SUCCESS;
            default:
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

    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
    }
}
