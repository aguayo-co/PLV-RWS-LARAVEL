<?php

namespace App\Http\Controllers;

use App\CreditsTransaction;
use App\Order;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Notifications\CreditsWithdraw;
use App\Notifications\CreditsApproved;
use App\Notifications\CreditsRejected;

class CreditsTransactionController extends Controller
{
    protected $modelClass = CreditsTransaction::class;

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
     * When user is not admin, limit to current user sales.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return;
        }

        return function ($query) use ($user) {
            return $query->where('user_id', $user->id);
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
        $userId = null;
        if (array_has($data, 'user_id')) {
            $userId = array_get($data, 'user_id');
        }
        if ($transaction) {
            $userId = $transaction->user_id;
        }
        $userId = auth()->id();
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
        // Upper limit is changes for admins and non-admins.
        // - Admins can add credits to users, their upper limit is
        //   virtually non existing (DB constrained).
        // - Non-admins need to withdraw at least 4000, so their upper limit
        //   is negative: -4000
        $upperLimit = auth()->user()->hasRole('admin') ? 9999999 : -4000;
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
        // Only admin can change transfer_status.
        if ($transaction && !auth()->user()->hasRole('admin')) {
            array_forget($data, 'transfer_status');
        }

        // In case a user is not specified, use logged in user.
        if (!$transaction && !array_get($data, 'user_id')) {
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
            $data['sale_id'] = null;
            $data['order_id'] = null;
        }

        return $data;
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
}
