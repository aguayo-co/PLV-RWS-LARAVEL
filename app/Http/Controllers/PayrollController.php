<?php

namespace App\Http\Controllers;

use App\CreditsTransaction;
use App\Payroll;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
                    $query->where('status', CreditsTransaction::STATUS_PENDING);
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

    protected function syncCreditsTransactions(Request $request, Model $payroll)
    {
        if ($request->has('credits_transactions_ids')) {
            $payroll->creditsTransactions()->sync($request->credits_transactions_ids);
        }
    }

    public function postUpdate(Request $request, Model $payroll)
    {
        $payroll = parent::postUpdate($request, $payroll);
        $this->syncCreditsTransactions($request, $payroll);
        return $payroll;
    }

    public function postStore(Request $request, Model $order)
    {
        $payroll = parent::postStore($request, $payroll);
        $this->syncCreditsTransactions($request, $payroll);
        return $payroll;
    }
}
