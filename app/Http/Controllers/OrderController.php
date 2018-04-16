<?php

namespace App\Http\Controllers;

use App\Address;
use App\Coupon;
use App\CreditsTransaction;
use App\Http\Controllers\Order\CouponRules;
use App\Http\Controllers\Order\OrderControllerRules;
use App\Http\Traits\CurrentUserOrder;
use App\Order;
use App\Payment;
use App\Product;
use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * This Controller handles actions taken on the Order and
 * on the Sale when those actions are initiated by the Buyer.
 *
 * A Buyer should not act directly on the Sale, but always through
 * the Order.
 */
class OrderController extends Controller
{
    use CouponRules;
    use CurrentUserOrder;
    use OrderControllerRules;

    protected $modelClass = Order::class;

    public static $allowedWhereIn = ['id', 'user_id'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('owner_or_admin')->only('show');
    }

    /**
     * Return a Closure that modifies the index query.
     * The closure receives the $query as a parameter.
     *
     * When user is not admin, limit to current user orders.
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
     * Get a Sale model for the given seller and order.
     * Create a new one if one does not exist.
     */
    protected function getSale($order, $sellerId)
    {
        $sale = $order->sales->firstWhere('user_id', $sellerId);
        if (!$sale) {
            $sale = new Sale();
            $sale->user_id = $sellerId;
            $sale->order_id = $order->id;

            $sale->status = Sale::STATUS_SHOPPING_CART;
            $sale->save();
        }
        return $sale;
    }

    /**
     * Get the products and group them by the user_id..
     */
    protected function getProductsByUser($productIds)
    {
        return Product::whereIn('id', $productIds)
            ->whereBetween('status', [Product::STATUS_APPROVED, Product::STATUS_AVAILABLE])
            ->get()->groupBy('user_id');
    }

    /**
     * Add products to the given cart/Order.
     */
    protected function addProducts($order, $productIds)
    {
        foreach ($this->getProductsByUser($productIds) as $userId => $products) {
            $sale = $this->getSale($order, $userId);
            $sale->products()->syncWithoutDetaching($products->pluck('id'));
        }

        return $order;
    }

    /**
     * Remove products from the given cart/Order.
     */
    protected function removeProducts($order, $productIds)
    {
        foreach ($order->sales as $sale) {
            $sale->products()->detach($productIds);
            $sale->load('products');
            if (!count($sale->products)) {
                $sale->delete();
            }
        }

        return $order;
    }

    /**
     * Process data for sales.
     *
     * @param  $order \App\Order The order the sales belong to
     * @param  $sales array Data to be applied to sales, keyed by sale id.
     */
    protected function processSalesData($order, $sales)
    {
        foreach ($sales as $saleId => $data) {
            $sale = $order->sales->firstWhere('id', $saleId);
            if ($shippingMethodId = array_get($data, 'shipping_method_id')) {
                $sale->shipping_method_id = $shippingMethodId;
                $sale->save();
            }
            if ($status = array_get($data, 'status')) {
                $sale->status = $status;
                $sale->save();
            }
        }
    }

    /**
     * Set validation messages for ValidationRules.
     */
    protected function validationMessages()
    {
        return [
            'add_product_ids.*.exists' => __('validation.available'),
            'remove_product_ids.*.exists' => __('validation.available')
        ];
    }

    /**
     * Return an array of validations rules to apply to the request data.
     *
     * @return array
     */
    protected function validationRules(array $data, ?Model $order)
    {
        $availableCredits = $order ? $order->user->credits : 0;
        return [
            'address_id' => [
                'integer',
                Rule::exists('addresses', 'id')->where(function ($query) use ($order) {
                    $query->where('user_id', $order->user_id);
                }),
            ],
            'phone' => 'string',

            'add_product_ids' => 'array',
            'add_product_ids.*' => [
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->whereBetween('status', [Product::STATUS_APPROVED, Product::STATUS_AVAILABLE]);
                }),
            ],

            'remove_product_ids' => 'array',
            'remove_product_ids.*' => 'integer|exists:products,id',

            'used_credits' => [
                'integer',
                'between:0,' . $availableCredits,
                $this->getorderInShoppingCartRule($order),
            ],

            'sales' => 'array',
            'sales.*' => [
                'bail',
                'array',
                $this->getIdIsValidRule($order),
                $this->getSaleIsValidRule($order),
            ],

            'sales.*.shipping_method_id' => [
                'bail',
                'integer',
                $this->getSaleInShoppingCartRule($order),
                $this->getShippingMethodRule($order)
            ],

            'sales.*.status' => [
                'bail',
                'integer',
                Rule::in([Sale::STATUS_RECEIVED, Sale::STATUS_COMPLETED]),
                $this->getStatusRule($order),
            ],

            'coupon_code' => array_merge([
                'bail',
                'nullable',
                'string',
                'exists:coupons,code',
            ], $this->getCouponRules($order)),

            'transfer_receipt' => [
                'image',
                $this->paymentIsTransferRule($order)
            ],

        ];
    }


    protected function alterFillData($data, Model $order = null)
    {
        // Never allow shipping_information to be used or passed.
        array_forget($data, 'shipping_information');

        $shippingInformation = data_get($order, 'shipping_information', []);

        // Calculate address from address_id.
        if ($addressId = array_get($data, 'address_id')) {
            $shippingInformation['address'] = Address::where('id', $addressId)->first()->toArray();
            $data['shipping_information'] = $shippingInformation;
        }

        // Set phone to shipping information.
        if ($phone = array_get($data, 'phone')) {
            $shippingInformation['phone'] = $phone;
            $data['shipping_information'] = $shippingInformation;
        }

        // Remove 'sales' from $data since it is not fillable.
        array_forget($data, 'sales');
        // Remove 'used_credits' from $data since it calculated, and not store din Order.
        array_forget($data, 'used_credits');

        // Calculate coupon_id form coupon_code.
        if (array_has($data, 'coupon_code')) {
            $couponCode = array_get($data, 'coupon_code');
            $data['coupon_id'] = $couponCode ? Coupon::where('code', $couponCode)->select('id')->first()->id : null;
        }
        array_forget($data, 'coupon_code');

        return $data;
    }

    /**
     * An alias for the show() method for the current logged in user.
     */
    public function getShoppingCart(Request $request)
    {
        return $this->show($request, $this->currentUserOrder());
    }

    /**
     * An alias for the update() method for the current logged in user.
     */
    public function updateShoppingCart(Request $request)
    {
        $order = $this->currentUserOrder();

        return $this->update($request, $order);
    }

    /**
     * Perform changes to associated Models.
     */
    public function postUpdate(Request $request, Model $order)
    {
        if ($addProductIds = $request->add_product_ids) {
            $this->addProducts($order, $addProductIds);
        }

        if ($removeProductIds = $request->remove_product_ids) {
            $this->removeProducts($order, $removeProductIds);
        }

        if ($sales = $request->sales) {
            $this->processSalesData($order, $sales);
        }

        if ($transferReceipt = $request->transfer_receipt) {
            $order->payments[0]->status = Payment::STATUS_PROCESSING;
            $order->payments[0]->transfer_receipt = $transferReceipt;
            $order->payments[0]->save();
        }

        $usedCredits = $request->used_credits;
        if ($usedCredits > 0) {
            CreditsTransaction::updateOrCreate(
                ['order_id' => $order->id, 'user_id' => $order->user->id, 'extra->origin' => 'order'],
                ['amount' => -$usedCredits, 'extra' => ['origin' => 'order']]
            );
        }
        if ((string) $usedCredits === "0") {
            $transaction = CreditsTransaction::where(
                ['order_id' => $order->id, 'user_id' => $order->user->id, 'extra->origin' => 'order']
            )->first();
            if ($transaction) {
                $transaction->delete();
            }
        }

        return parent::postUpdate($request, $order);
    }
}
