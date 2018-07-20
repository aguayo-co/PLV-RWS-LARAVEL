<?php

namespace App\Http\Controllers;

use App\CreditsTransaction;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Notifications\CreditsWithdraw;
use App\Notifications\CreditsApproved;
use App\Notifications\CreditsRejected;

class CreditsTransactionController extends Controller
{
    protected $modelClass = CreditsTransaction::class;

    public static $allowedWhereIn = ['id', 'payroll_id'];
    public static $allowedWhereBetween = ['transfer_status'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin')->only('show');
        $this->middleware('role:admin')->only('update');
        $this->middleware(self::class . '::validateUserCanCreateTransaction')->only(['store']);
    }

    /**
     * Middleware that validates permissions to change CreditsTransaction.
     */
    public static function validateUserCanCreateTransaction($request, $next)
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // When the user is not admin, it can ONLY create a transfer request.
        // When creating a transfer_request `transfer_status` must be 0.
        // If transfer_status is not `0` do not allow user to continue.
        if ((string) $request->transfer_status !== '0') {
            throw ValidationException::withMessages([
                'transfer_status' => [__('Invalid value.')],
            ]);
        }

        return $next($request);
    }

    /**
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        return function ($query) {
            $relatedTo = array_get(request()->query('filter'), 'related_to');
            switch ($relatedTo) {
                case 'orders':
                    $query = $query->whereNotNull('order_id')
                        ->whereNull('sale_id')
                        ->whereNull('transfer_status');
                    break;
                case 'sales':
                    $query = $query->whereNull('order_id')
                        ->whereNotNull('sale_id')
                        ->whereNull('transfer_status');
                    break;
                case 'none':
                    $query = $query->whereNull('order_id')
                        ->whereNull('sale_id')
                        ->whereNull('transfer_status');
                    break;
            }

            // When user is not admin, limit to current user transactions.
            $user = auth()->user();
            if (!$user->hasRole('admin')) {
                $query = $query->where('user_id', $user->id);
            }

            return $query;
        };
    }

    /**
     * Return the user which should be used to validate other fields with.
     *
     * This is the user that will be set on the CreditsTransaction if the
     * request is completed.
     */
    protected function getValidationUser(array $data, ?Model $transaction)
    {
        $userId = auth()->id();
        if (array_has($data, 'user_id')) {
            $userId = array_get($data, 'user_id');
        }
        if ($transaction) {
            $userId = $transaction->user_id;
        }
        return User::withCredits()->find($userId);
    }

    protected function validationRules(array $data, ?Model $transaction)
    {
        $required = !$transaction ? 'required|' : '';
        $user = $this->getValidationUser($data, $transaction);
        $statuses = CreditsTransaction::getStatuses();
        // Lower limit is the maximum amount a user can withdraw.
        // It has to be negative or 0,
        $lowerLimit = min(-data_get($user, 'credits', 0), 0);
        // Upper limit changes for admins and non-admins.
        // - Admins can add credits to users, their upper limit is
        //   virtually non existing (DB constrained).
        // - Non-admins need to withdraw at least 4000, so their upper limit
        //   is negative: -4000, and they need to withdraw the full amount
        //   of available credits they have, so can be even lower.
        $upperLimit = auth()->user()->hasRole('admin') ? 9999999 : min(-4000, $lowerLimit);
        return [
            'user_id' => [
                'integer',
                'exists:users,id',
                // Once a transaction has been created, user can't be changed,
                // not even by admins. A new one has to be created instead.
                $transaction ? Rule::in([$transaction->user_id]) : null,
            ],
            'amount' => [
                trim($required, '|'),
                'integer',
                "between:$lowerLimit,$upperLimit",
            ],
            'commission' => 'nullable|integer|min:0|empty_with:sale_id|empty_with:transfer_status',
            'sale_id' => [
                'empty_with:order_id',
                'empty_with:transfer_status',
                'integer',
                Rule::exists('sales', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', data_get($user, 'id'));
                }),
            ],
            'order_id' => [
                'empty_with:sale_id',
                'empty_with:transfer_status',
                'integer',
                Rule::exists('orders', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', data_get($user, 'id'));
                }),
            ],
            'transfer_status' => [
                'empty_with:sale_id',
                'empty_with:order_id',
                'integer',
                'max:' . (auth()->user()->hasRole('admin') ? last($statuses) : $statuses['STATUS_PENDING']),
                Rule::in($statuses),
            ],
            'extra' => $required . 'array',
        ];
    }

    protected function alterFillData($data, Model $transaction = null)
    {
        // In case a user is not specified, use logged in user.
        if (!$transaction && !array_has($data, 'user_id')) {
            $data['user_id'] = auth()->user()->id;
        }

        // Only one of these fields should be set in the model:
        $orderId = array_get($data, 'order_id');
        $saleId = array_get($data, 'sale_id');
        $transferStatus = array_get($data, 'transfer_status');

        if ($saleId) {
            $data['order_id'] = null;
            $data['transfer_status'] = null;
        }
        if ($orderId) {
            $data['sale_id'] = null;
            $data['transfer_status'] = null;
        }
        if ($transferStatus !== null) {
            if (!$transaction) {
                $this->setBankAccount($data);
            }
            $data['sale_id'] = null;
            $data['order_id'] = null;
        }

        $this->setCommission($data, $transaction);

        return $data;
    }

    protected function setCommission(&$data, $transaction)
    {
        // Never allow sent data from non admins.
        if (!auth()->user()->hasRole('admin')) {
            array_forget($data, 'commission');
        }

        // If we received valid data, allow it.
        if (array_has($data, 'commission')) {
            return;
        }

        // Do not calculate for existing transactions.
        if ($transaction) {
            return;
        }

        $orderId = array_get($data, 'order_id');
        $saleId = array_get($data, 'sale_id');
        $transferStatus = array_get($data, 'transfer_status');

        // No commission allowed for Transactions with OrderId.
        if ($orderId) {
            $data['commission'] = null;
            return;
        }

        // When from a sale, it is the value of the commission for that sale.
        if ($saleId) {
            $sale = Sale::find($saleId);
            $data['commission'] = $sale->commission;
            return;
        }

        // If a withdraw, use sum of commission until now.
        if ($transferStatus !== null) {
            $user = User::withCredits()->find(array_get($data, 'user_id'));
            $data['commission'] = -data_get($user, 'commissions', 0);
        }
    }

    protected function setBankAccount(&$data)
    {
        $userId = array_get($data, 'user_id');
        if (!$userId) {
            return;
        }

        $user = User::find($userId);
        $data['extra']['bank_account'] = $user->bank_account;
    }

    public function postStore(Request $request, Model $transaction)
    {
        $transaction = parent::postStore($request, $transaction);

        if ($transaction->transfer_status === CreditsTransaction::STATUS_PENDING) {
            $transaction->user->notify(new CreditsWithdraw(['transaction' => $transaction]));
        }

        return $transaction;
    }

    public function postUpdate(Request $request, Model $transaction)
    {
        $statusChanged = array_get($transaction->getChanges(), 'transfer_status');
        $transaction = parent::postUpdate($request, $transaction);

        switch ($statusChanged) {
            case CreditsTransaction::STATUS_COMPLETED:
                $transaction->user->notify(new CreditsApproved(['transaction' => $transaction]));
                break;

            case CreditsTransaction::STATUS_REJECTED:
                $transaction->user->notify(new CreditsRejected(['transaction' => $transaction]));
                break;
        }

        return $transaction;
    }

    protected function setVisibility(Collection $collection)
    {
        $loggedUser = auth()->user();
        if ($loggedUser && $loggedUser->hasRole('admin')) {
            $collection->load(['user']);
        }
    }
}
