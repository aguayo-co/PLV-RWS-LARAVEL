<?php

namespace App\Http\Controllers;

use App\CreditsTransaction;
use App\Payroll;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayrollController extends AdminController
{
    protected $modelClass = Payroll::class;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('role:admin');
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

    /**
     * Bring menu items for each menu with up to two levels of children.
     */
    protected function setVisibility(Collection $collection)
    {
        $collection->load('creditsTransactions');
        $collection->each(function ($payroll) {
            $payroll->append(['credits_transactions_ids']);
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
