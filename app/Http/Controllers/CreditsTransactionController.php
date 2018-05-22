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
        $availableCredits = -data_get($user, 'credits', 0);
        $upperLimit = auth()->user()->hasRole('admin') ? 9999999 : 0;
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
                "between:$availableCredits,$upperLimit",
            ],
            'sale_id' => [
                'nullable',
                'empty_with:order_id',
                'empty_with:transfer_status',
                'integer',
                Rule::exists('sales', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', data_get($user, 'id'));
                }),
            ],
            'order_id' => [
                'nullable',
                'empty_with:sale_id',
                'empty_with:transfer_status',
                'integer',
                Rule::exists('orders', 'id')->where(function ($query) use ($user) {
                    $query->where('user_id', data_get($user, 'id'));
                }),
            ],
            'transfer_status' => [
                'nullable',
                'empty_with:sale_id',
                'empty_with:order_id',
                'integer',
                Rule::in(CreditsTransaction::getStatuses()),
            ],
            'extra' => $required . 'array',
        ];
    }

    protected function alterFillData($data, Model $transaction = null)
    {
        // Only admin can change transfer_status.
        if (!auth()->user()->hasRole('admin')) {
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
        if ($transferStatus) {
            $data['sale_id'] = null;
            $data['order_id'] = null;
        }

        return $data;
    }

    public function postStore(Request $request, Model $transaction)
    {
        $transaction = parent::postStore($request, $transaction);

        if (!$transaction->order && !$transaction->sale && $transaction->amount < 0) {
            $transaction->user->notify(new CreditsWithdraw(['transaction' => $transaction]));
        }

        return $transaction;
    }
}
