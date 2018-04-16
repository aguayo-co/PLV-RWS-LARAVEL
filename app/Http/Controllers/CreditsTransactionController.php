<?php

namespace App\Http\Controllers;

use App\CreditsTransaction;
use App\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CreditsTransactionController extends Controller
{
    protected $modelClass = CreditsTransaction::class;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin')->only('show');
        $this->middleware(self::class . '::validateUserCanModifyTransaction')->only(['delete', 'update']);
    }

    /**
     * Middleware that validates permissions to change CreditsTransaction.
     */
    public static function validateUserCanModifyTransaction($request, $next)
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        $transaction = $request->route()->parameters['credits_transaction'];

        $order = $transaction->order;

        if ($order && Order::STATUS_SHOPPING_CART < $order->status) {
            abort(Response::HTTP_FORBIDDEN, 'User not allowed to modify transaction for order not in SHopping Cart.');
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

    protected function getValidationUserId(array $data, ?Model $transaction)
    {
        if (array_has($data, 'user_id')) {
            return array_get($data, 'user_id');
        }
        if ($transaction) {
            return $transaction->user_id;
        }
        return auth()->user()->id;
    }

    protected function validationRules(array $data, ?Model $transaction)
    {
        $required = !$transaction ? 'required|' : '';
        $userId = $this->getValidationUserId($data, $transaction);
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
                'between:-9999999,9999999',
                $this->getIsOutflowValidationRule(),
            ],
            'sale_id' => [
                'nullable',
                'empty_with:order_id',
                'integer',
                Rule::exists('sales', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }),
            ],
            'order_id' => [
                'nullable',
                'empty_with:sale_id',
                'integer',
                Rule::exists('orders', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }),
            ],
            'extra' => $required . 'array',
        ];
    }

    /**
     * Rule that validates that a user can only use credits, and only
     * admin can add credits.
     */
    public static function getIsOutflowValidationRule()
    {
        return function ($attribute, $value, $fail) {
            if (auth()->user()->hasRole('admin')) {
                return;
            }
            if ($value > 0) {
                return $fail(__('validation.max.numeric', ['max' => 0]));
            }
        };
    }

    protected function alterFillData($data, Model $transaction = null)
    {
        if (!$transaction && !array_get($data, 'user_id')) {
            $data['user_id'] = auth()->user()->id;
        }

        $orderId = array_get($data, 'order_id');
        $saleId = array_get($data, 'sale_id');

        if ($saleId) {
            $data['order_id'] = null;
        }
        if ($orderId) {
            $data['sale_id'] = null;
        }

        return $data;
    }
}
