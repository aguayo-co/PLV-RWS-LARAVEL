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
use Illuminate\Database\Eloquent\Collection;
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
    public static $allowedWhereBetween = ['status'];

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
            $shippingMethodId = array_get($data, 'shipping_method_id');
            if ($shippingMethodId) {
                $sale->shipping_method_id = $shippingMethodId;
                $sale->save();
            }
            $status = array_get($data, 'status');
            if ($status) {
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
        $availableCredits = $order ? data_get($order->user()->withCredits()->first(), 'credits', 0) : 0;
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
                'between:0,' . max($availableCredits, 0),
                $this->getOrderInShoppingCartRule($order),
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
        $addressId = array_get($data, 'address_id');
        if ($addressId) {
            $shippingInformation['address'] = Address::where('id', $addressId)->first()->toArray();
            $data['shipping_information'] = $shippingInformation;
        }

        // Set phone to shipping information.
        $phone = array_get($data, 'phone');
        if ($phone) {
            $shippingInformation['phone'] = $phone;
            $data['shipping_information'] = $shippingInformation;
        }

        // Remove 'sales' from $data since it is not fillable.
        array_forget($data, 'sales');
        // Remove 'used_credits' from $data since it calculated, and not stored in Order.
        array_forget($data, 'used_credits');

        // Calculate coupon_id form coupon_code.
        if (array_has($data, 'coupon_code')) {
            $couponCode = array_get($data, 'coupon_code');
            $data['coupon_id'] = $couponCode ? Coupon::where('code', $couponCode)->pluck('id')->first() : null;
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
        $addProductIds = $request->add_product_ids;
        if ($addProductIds) {
            $this->addProducts($order, $addProductIds);
        }

        $removeProductIds = $request->remove_product_ids;
        if ($removeProductIds) {
            $this->removeProducts($order, $removeProductIds);
        }

        $sales = $request->sales;
        if ($sales) {
            $this->processSalesData($order, $sales);
        }

        $transferReceipt = $request->transfer_receipt;
        if ($transferReceipt) {
            $order->payments[0]->status = Payment::STATUS_PROCESSING;
            $order->payments[0]->transfer_receipt = $transferReceipt;
            $order->payments[0]->save();
        }

        if ($request->has('used_credits')) {
            $this->setOrderCredits($request->used_credits, $order);
        }

        // Only use address if Chilexpress is selected for at least one Sale.
        // This is performed after updating the sales, in case shipping method were changed.
        $shippingInformation = data_get($order, 'shipping_information', []);
        if (array_has($shippingInformation, 'address') && !$order->sales->where('is_chilexpress', true)->count()) {
            array_forget($shippingInformation, 'address');
            $order->shipping_information = $shippingInformation;
            $order->save();
        }

        return parent::postUpdate($request, $order);
    }

    /**
     * Set the credits to be used in the order by creating, updating or deleting
     * a CreditsTransaction model.
     */
    protected function setOrderCredits($usedCredits, $order)
    {
        $hasCredits = false;

        $transactions = CreditsTransaction::where(
            ['order_id' => $order->id, 'user_id' => $order->user->id]
        )->get();

        // If user is using credits with this order.
        // Leave one transaction alone to set the credits on that one.
        if ((int)$usedCredits !== 0) {
            $hasCredits = true;
            $transactions->shift();
        }

        // Delete any extra transactions, as before paying an order
        // there should be only one transaction per order.
        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        if ($hasCredits) {
            CreditsTransaction::updateOrCreate(
                ['order_id' => $order->id, 'user_id' => $order->user->id],
                ['amount' => -$usedCredits, 'extra' => ['reason' => __('prilov.credits.reasons.orderPayment')]]
            );
        }
    }

    protected function setVisibility(Collection $collection)
    {
        $collection->load([
            'coupon',
            'payments',
            'user',

            'sales.products.brand',
            'sales.products.condition',
            'sales.products.size',

            'sales.returns',
            'sales.shippingMethod',
            'sales.user.shippingMethods',
        ]);
        $collection->each(function ($order) {
            $order->user->makeVisible(['email', 'phone']);
            $order->sales->each(function ($sale) {
                if (Sale::STATUS_PAYED <= $sale->status && $sale->status < Sale::STATUS_CANCELED) {
                    $sale->user->makeVisible(['email', 'phone']);
                }
                $sale->append([
                    'shipping_cost',
                    'allow_chilexpress',
                    'is_chilexpress',
                    'total',
                ]);
            });
            $order->append([
                'total',
                'used_credits',
                'due',
                'coupon_discount',
                'shipping_cost',
            ]);
        });
    }
}
