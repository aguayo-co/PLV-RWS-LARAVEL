<?php

namespace App\Http\Controllers;

use App\CreditsTransaction;
use App\Payroll;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;

class PayrollController extends AdminController
{
    protected $modelClass = Payroll::class;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('role:admin')->except('download');
    }

    /**
     * Return an array of validations rules to apply to the request data.
     *
     * @return array
     */
    protected function validationRules(array $data, ?Model $payroll)
    {
        return [
            'credits_transactions_ids' => 'array',
            'credits_transactions_ids.*' => [
                'integer',
                Rule::exists('credits_transactions', 'id')->where(function ($query) {
                    $query->whereNotNull('transfer_status');
                    $query->whereNull('payroll_id');
                }),
            ],

            'completed_credits_transactions_ids' => 'array',
            'completed_credits_transactions_ids.*' => [
                'integer',
                Rule::exists('credits_transactions', 'id')->where(function ($query) {
                    $query->whereNotNull('payroll_id');
                }),
            ],

            'rejected_credits_transactions_ids' => 'array',
            'rejected_credits_transactions_ids.*' => [
                'integer',
                Rule::exists('credits_transactions', 'id')->where(function ($query) {
                    $query->whereNotNull('payroll_id');
                }),
            ],
        ];
    }

    public function download(Request $request, Model $payroll)
    {
        $transfers = [];
        $query = $payroll->creditsTransactions();
        if (!$request->complete) {
            $query = $query->where('transfer_status', CreditsTransaction::STATUS_PENDING);
        }
        $transactions = $query->get();

        foreach ($transactions as $transaction) {
            $transfer = data_get($transaction, 'extra.bank_account', []);
            // Chilean rut has 8 digits and a verification number.
            // Ignore anything else.
            $cleanRut = preg_replace('/[^0-9]/', '', data_get($transfer, 'rut', ''));
            $transfer['rut'] = [
                substr($cleanRut, 0, 8),
                substr($cleanRut, 8, 1)
            ];
            $transfer['amount'] = -$transaction->amount;
            $transfer['commission'] = -$transaction->commission;
            $transfer['email'] = $transaction->user->email;
            $transfers[] = $transfer;
        }

        return response()
            ->view('downloads.payroll', [
                'payroll' => $payroll,
                'transfers' => $transfers,
            ])
            ->header('Content-Type', 'text/xml, application/xml')
            ->header('Content-Disposition', 'attachment; filename=payroll-' . $payroll->id . '.xml');
    }

    /**
     * Bring menu items for each menu with up to two levels of children.
     */
    protected function setVisibility(Collection $collection)
    {
        $collection->load('creditsTransactions');
        $collection->each(function ($payroll) {
            $payroll->append(['credits_transactions_ids', 'download_urls']);
        });
    }

    public function postUpdate(Request $request, Model $payroll)
    {
        $toComplete = $payroll->creditsTransactions
            ->whereNotIn('transfer_status', [CreditsTransaction::STATUS_COMPLETED])
            ->whereIn('id', $request->completed_credits_transactions_ids);
        foreach ($toComplete as $creditsTransaction) {
            $creditsTransaction->transfer_status = CreditsTransaction::STATUS_COMPLETED;
            $creditsTransaction->save();
        }

        $toReject = $payroll->creditsTransactions
            ->whereNotIn('transfer_status', [CreditsTransaction::STATUS_REJECTED])
            ->whereIn('id', $request->rejected_credits_transactions_ids);
        foreach ($toReject as $creditsTransaction) {
            $creditsTransaction->transfer_status = CreditsTransaction::STATUS_REJECTED;
            $creditsTransaction->save();
        }

        return parent::postUpdate($request, $payroll);
    }
}
