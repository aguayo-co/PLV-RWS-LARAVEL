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

    public static $allowedWhereIn = ['id', 'user_id'];
    public static $allowedWhereBetween = ['status'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin')->only('show');
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
            'creditsTransactions',
            'order.creditsTransactions',
            'order.user',
            'products.brand',
            'products.condition',
            'products.size',
            'products.user',
            'returns',
            'shippingMethod',
            'user',
        ]);
        $collection->each(function ($sale) {
            $sale->user->makeVisible(['email', 'phone']);
            if (Sale::STATUS_PAYED <= $sale->status && $sale->status < Sale::STATUS_CANCELED) {
                $sale->order->user->makeVisible(['email', 'phone']);
            }
            if ($sale->status <= Sale::STATUS_PAYED || $sale->status === Sale::STATUS_CANCELED) {
                $sale->order->makeHidden(['shipping_information']);
            }

            $sale->append([
                'shipping_cost',
                'allow_chilexpress',
                'is_chilexpress',
                'coupon_discount',
            ]);
        });
    }
}
