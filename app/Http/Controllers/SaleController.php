<?php

namespace App\Http\Controllers;

use App\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * This Controller handles actions initiated by the Seller,
 * or by Admins but when modifying a Sale for a Seller.
 *
 * Actions that should be taken by the Buyer should be handled in the
 * Order Controller.
 * A Buyer should not act directly on the Sale, but always through
 * the Order.
 */
class SaleController extends Controller
{
    protected $modelClass = Sale::class;

    public static $allowedWhereIn = ['id', 'user_id', 'order_id'];
    public static $allowedWhereBetween = ['status'];
    public static $allowedWhereHas = [
        'buyer_id' => 'order,user_id',
        'buyer_email' => 'order.user,email',
        'buyer_full_name' => 'order.user,full_name',
        'user_email' => 'user,email',
        'user_full_name' => 'user,full_name',
        'product_id' => 'products',
        'product_title' => 'products,title',
    ];
    public static $allowedOrderBy = ['id', 'created_at', 'updated_at', 'status_history->20->date'];
    public static $orderByAliases = [
        'payment_date' => 'status_history->20->date',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin')->only('show');
        $this->middleware(self::class . '::validateCanBeDeleted')->only(['delete']);
    }

    /**
     * Middleware that validates permissions to delete a sale.
     */
    public static function validateCanBeDeleted($request, $next)
    {
        $sale = $request->route()->parameters['sale'];
        if ($sale->status < Sale::STATUS_PAYMENT) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'Sale can not be deleted.');
    }

    /**
     * When user is not admin, limit to current user sales.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        $user = auth()->user();
        $showAll = array_get(request()->query('filter'), 'all');
        if ($user->hasRole('admin') && $showAll) {
            return;
        }

        // Display sales with buyers information.
        // Visibility should be different for this query.
        // Check setVisibility().
        // Limit to orders of the current logged in user.
        if (request()->get('buyer')) {
            return function ($query) use ($user) {
                $query = $query->whereHas('order', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            };
        }

        return function ($query) use ($user) {
            return $query->where('user_id', $user->id);
        };
    }

    /**
     * All posible rules for all posible states are set here.
     * These rules validate that the data is correct, not whether it
     * can be used on the current Sale given its status.
     *
     * @return array
     */
    protected function validationRules(array $data, ?Model $sale)
    {
        $validStatuses = [Sale::STATUS_SHIPPED, Sale::STATUS_DELIVERED, Sale::STATUS_CANCELED];
        return [
            'shipment_details' => [
                'array',
                $this->getCanSetShipmentDetailsRule($sale),
            ],
            'status' => [
                'bail',
                'integer',
                Rule::in($validStatuses),
                $this->getStatusRule($sale),
                // Do not go back in status. Except for Delivered, that can be changed to shipped.
                'min:' . ($sale->status === Sale::STATUS_DELIVERED ? Sale::STATUS_SHIPPED : $sale->status),
            ],
        ];
    }

    /**
     * Rule that validates that a Sale status is valid.
     */
    protected function getStatusRule($sale)
    {
        return function ($attribute, $value, $fail) use ($sale) {
            if ((int)$value === Sale::STATUS_CANCELED && !auth()->user()->hasRole('admin')) {
                return $fail(__('Only an Admin can cancel a Sale.'));
            }
            // Order needs to be payed.
            if ($sale->status < Sale::STATUS_PAYED) {
                return $fail(__('La orden no ha sido pagada.'));
            }
        };
    }

    /**
     * Rule that validates that Shipment Details can be set.
     */
    protected function getCanSetShipmentDetailsRule($sale)
    {
        return function ($attribute, $value, $fail) use ($sale) {
            if (!$sale) {
                return $fail(__('No se puede agregar esta información durante la creación.'));
            }
            // Sale needs to be payed.
            if ($sale->status < Sale::STATUS_PAYED) {
                return $fail(__('La venta no ha sido pagada.'));
            }
            // Sale shipped already.
            if (Sale::STATUS_RECEIVED < $sale->status) {
                return $fail(__('Información ya no se puede modificar.'));
            }
        };
    }

    protected function setVisibility(Collection $collection)
    {
        $collection->load([
            'returns',
            'shippingMethod',
            'user',

            'order.coupon',
            'order.user',

            'products.brand',
            'products.condition',
            'products.size',
            'products.user',
        ]);

        // Optional information.
        // Permissions checked in alterIndexQuery
        if (request()->get('buyer')) {
            $collection->loadMissing([
                'order.creditsTransactions',
                'order.payments',
                // When printing with buyers info, the order needs all the sales
                // to calculate information.
                // Eager load sales again with the products.
                // This might seem redundant, but since from the sale there
                // is information that we do not have, we need the order with all
                // its related models loaded to calculate everything.
                'order.sales.products',
                'order.sales.shippingMethod',
            ]);
        }

        $collection->each(function ($sale) {
            $sale->user->makeVisible(['email', 'phone']);
            // Show contact data only when order has been successful.
            // Or for admins.
            $loggedUser = auth()->user();
            if (($loggedUser && $loggedUser->hasRole('admin'))
                || (Sale::STATUS_PAYED <= $sale->status && $sale->status < Sale::STATUS_CANCELED)) {
                $sale->order->user->makeVisible(['email', 'phone']);
            }
            if ($sale->status < Sale::STATUS_PAYED || $sale->status === Sale::STATUS_CANCELED) {
                $sale->order->makeHidden(['shipping_information']);
            }

            $sale->append([
                'coupon_discount',
                'shipping_cost',
                'allow_chilexpress',
                'is_chilexpress',
                'total',
                'commission',
            ]);

            // Optional information.
            // Permissions checked in alterIndexQuery
            if (request()->get('buyer')) {
                $sale->order->makeHidden(['sales', 'creditsTransactions']);
                $sale->append([
                    'used_credits',
                ]);
                $sale->order->append([
                    'active_payment',
                    'total',
                    'used_credits',
                    'due',
                    'coupon_discount',
                    'shipping_cost',
                ]);
            }
        });
    }
}
