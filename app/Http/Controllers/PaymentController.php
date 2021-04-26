<?php

namespace App\Http\Controllers;

use App\Events\PaymentAborted;
use App\Events\PaymentStarted;
use App\Events\PaymentSuccessful;
use App\Gateways\GatewayManager;
use App\Http\Controllers\Order\CouponRules;
use App\Http\Traits\CurrentUserOrder;
use App\Order;
use App\Payment;
use App\Product;
use App\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    use CouponRules;
    use CurrentUserOrder;

    protected $modelClass = Payment::class;

    public static $allowedWhereIn = ['id', 'gateway', 'order_id'];
    public static $allowedWhereBetween = ['status'];

    public function __construct()
    {
        parent::__construct();

        $this->middleware('role:admin')->only(['index', 'update']);
        $this->middleware(self::class . '::validateUserIsOwner')->only(['generatePayment']);
    }

    /**
     * Middleware that validates permissions get the payment.
     */
    public static function validateUserIsOwner($request, $next)
    {
        $user = auth()->user();
        $order = $request->route()->parameters['order'];

        if ($user->is($order->user)) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'Only owner can get payment.');
    }

    /**
     * Return an array of validations rules to apply to the request data.
     *
     * @return array
     */
    protected function validationRules(array $data, ?Model $order)
    {
        return [
            // This is for orders with no products.
            'total' => 'integer|min:1'
        ];
    }

    /**
     * Validate that an order can be sent to Checkout.
     */
    protected function validateOrderCanCheckout($order)
    {
        if ($order->due < 0) {
            throw ValidationException::withMessages([
                'order.due' => ['Invalid total value.'],
            ]);
        }

        if ($order->used_credits) {
            $availableCredits = data_get($order->user()->withCredits()->first(), 'credits', 0);
            if ($availableCredits < $order->used_credits) {
                throw ValidationException::withMessages([
                    'order.used_credits' => ['Invalid credits value.'],
                ]);
            }
        }

        switch ($order->status) {
            case Order::STATUS_TRANSACTION:
                $this->validateTransactionOrder($order);
                break;

            case Order::STATUS_SHOPPING_CART:
                $this->validateShoppingCartOrder($order);
                break;

            default:
                throw ValidationException::withMessages([
                    'order.status' => ['Order can not proceed to Check Out.'],
                ]);
        }
    }

    protected function validateTransactionOrder($order)
    {
        if ($order->sales->count()) {
            throw ValidationException::withMessages([
                'order.sales' => ['Transaction order can not have sales.'],
            ]);
        }

        if ($order->coupon) {
            throw ValidationException::withMessages([
                'order.coupon' => ['Transaction order can not have coupon.'],
            ]);
        }
    }

    protected function validateShoppingCartOrder($order)
    {
        if ($order->coupon) {
            Validator::make(
                ['coupon_code' => $order->coupon->code],
                ['coupon_code' => $this->getCouponRules($order)]
            )->validate();
        }

        if (!$order->products->where('saleable', true)->count()) {
            throw ValidationException::withMessages([
                'order.products' => ['No products in shopping cart.'],
            ]);
        }

        if ($order->products->where('saleable', false)->count()) {
            throw ValidationException::withMessages([
                'order.products' => ['Some products are not available anymore.'],
            ]);
        }

        if ($order->sales->where('shipping_method_id', null)->count()) {
            throw ValidationException::withMessages([
                'order.sales.shipping_method_id' => ['Some sales do not have a ShippingMethod.'],
            ]);
        }

        if (!data_get($order, 'shipping_information.phone')) {
            throw ValidationException::withMessages([
                'order.shipping_information.phone' => ['Order needs shipping phone.'],
            ]);
        }

        $this->validateChileExpressIsAvailable($order);
    }

    /**
     * Validate that delivery with Chilexpress is allowed if any of the sales
     * has been selected to be delivered with them.
     */
    protected function validateChileExpressIsAvailable(Model $order)
    {
        $order->sales->each(function ($sale) {
            if ($sale->is_chilexpress && !$sale->shipTo) {
                throw ValidationException::withMessages([
                    'order.sales.shipping_information.address' => ['Order needs shipping address.'],
                ]);
            }

            if ($sale->is_chilexpress && !$sale->allow_chilexpress) {
                throw ValidationException::withMessages([
                    'order.sales.shipping_information.address' => ['Address not covered by Chilexpress.'],
                ]);
            }
        });
    }

    /**
     * Validates that an order is fresh.
     */
    protected function validateIsFresh(Order $order, $abort)
    {
        $freshOrder = Order::find($order->id)->load('sales.products');
        if (
            !$this->orderIsFresh($order, $freshOrder)
            || !$this->salesAreFresh($order, $freshOrder)
        ) {
            if ($abort) {
                event(new PaymentAborted($order));
            }
            throw ValidationException::withMessages([
                'order' => ['Order modified while generating payment information.'],
            ]);
        };

        if (!$this->productsAreFresh($order, $freshOrder)) {
            if ($abort) {
                event(new PaymentAborted($order));
            }
            throw ValidationException::withMessages([
                'order' => ['Products modified while generating payment information.'],
            ]);
        };
    }

    /**
     * Checks that an order is fresh.
     */
    protected function orderIsFresh(Order $order, Order $freshOrder)
    {
        // Before PHP7.3 this is almost useless.
        // PDO from PHP < 7.3 does not respect microseconds in
        // timestamps (and we are not storing them).
        // Ideally after upgrading to PHP 7.3, timestamps columns should
        // store microseconds and this comparison will be more useful.
        if ($freshOrder->updated_at != $order->updated_at) {
            return false;
        }

        // Order value should be the same.
        if (
            $freshOrder->total != $order->total
            || $freshOrder->used_credits != $order->used_credits
            || $freshOrder->coupon_discount != $order->coupon_discount
            || $freshOrder->shipping_cost != $freshOrder->shipping_cost
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks that an order sales are fresh.
     */
    protected function salesAreFresh(Order $order, Order $freshOrder)
    {
        $salesIds = $order->sales->pluck('id');
        $freshSalesIds = $freshOrder->sales->pluck('id');

        // Sales should be the same.
        if (
            $salesIds->diff($freshSalesIds)->count()
            || $freshSalesIds->diff($salesIds)->count()
        ) {
            return false;
        }

        // Sales should have not been updated.
        foreach ($freshOrder->sales as $freshSale) {
            $sale = $order->sales->firstWhere('id', $freshSale->id);
            // Before PHP7.3 this is almost useless.
            // PDO from PHP < 7.3 does not respect microseconds in
            // timestamps (and we are not storing them).
            // Ideally after upgrading to PHP 7.3, timestamps columns should
            // store microseconds and this comparison will be more useful.
            if ($freshSale->updated_at != $sale->updated_at) {
                return false;
            }
        };

        return true;
    }

    /**
     * Checks that an order products are fresh.
     */
    protected function productsAreFresh(Order $order, Order $freshOrder)
    {
        $productsIds = $order->products->pluck('id');
        $freshProductsIds = $freshOrder->products->pluck('id');

        // Products should be the same.
        if (
            $productsIds->diff($freshProductsIds)->count()
            || $freshProductsIds->diff($productsIds)->count()
        ) {
            return false;
        }

        // Products should have not been updated.
        foreach ($freshOrder->products as $freshProduct) {
            $product = $order->products->firstWhere('id', $freshProduct->id);
            // Before PHP7.3 this is almost useless.
            // PDO from PHP < 7.3 does not respect microseconds in
            // timestamps (and we are not storing them).
            // Ideally after upgrading to PHP 7.3, timestamps columns should
            // store microseconds and this comparison will be more useful.
            if ($freshProduct->updated_at != $product->updated_at) {
                return false;
            }
            // Since date comparison is not that useful right now, check
            // that the product has the same status, which is the most
            // important property to allow the item to be bought.
            if ($freshProduct->status != $product->status) {
                return false;
            }
        };

        return true;
    }

    /**
     * Create a new payment for the given order, with the given gateway.
     */
    protected function createPayment(GatewayManager $gatewayManager, Model $order)
    {
        $payment = new Payment();
        $payment->gateway = $gatewayManager->getName();
        $payment->status = Payment::STATUS_PENDING;
        $payment->order_id = $order->id;

        return $payment;
    }

    /**
     * Only change we accept on a payment is canceling it.
     */
    public function update(Request $request, Model $payment)
    {
        if ($request->cancel === 'cancel') {
            Artisan::call('payments:pending-to-canceled', ['payment' => $payment->id]);
        }

        $payment = $payment->fresh();
        $this->setVisibility(Collection::wrap($payment));

        return $payment;
    }

    /**
     * Create a new payment for the current user.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $total = array_get($data, 'total');

        // If we get a 'total' argument, we need an order with no products.
        switch (true) {
            case $total === null:
                $order = $this->currentUserOrder();
                break;

            default:
                $order = $this->currentUserOrder(Order::STATUS_TRANSACTION);
                $order->extra = [
                    'product' => 'credits',
                    'total' => $total
                ];
                $order->save();
        }

        return $this->generatePayment($request, $order);
    }

    /**
     * Create a new payment for the given order.
     */
    public function generatePayment(Request $request, Order $order)
    {
        DB::transaction(function () use ($request, $order) {
            // Lock model that are currently loaded in the order
            // so we can verify that are not modified while we freeze them.
            Order::lockForUpdate()->find($order->id);
            Sale::lockForUpdate()->find($order->sales->pluck('id'));
            Product::lockForUpdate()->find($order->products->pluck('id'));

            $this->validateOrderCanCheckout($order);

            $gatewayName = $request->query('gateway');
            if ($order->due > 0 && $gatewayName === 'free') {
                throw ValidationException::withMessages([
                    'gateway' => ['Invalid gateway.'],
                ]);
            }

            // There is a RaceCondition when a user adds a product to the
            // shopping cart at the same time as requested a payment.
            // The product gets added to the order but it does not gets frozen
            // nor validated becase it was added after we loaded the data here.
            //
            // We run the following check twice:
            // Load a fresh copy and check it has not been updated.
            $this->validateIsFresh($order, false);
            event(new PaymentStarted($order));
        });

        // Since we had a Lock on the rows, allow 1 second for any pending
        // transactions from other processes to complete.
        // This is a safe time to wait and then check again if any
        // modifications were made to the order by any other process.
        // Only transactions that were started while we had the lock
        // should be modifying the rows. Any new transactions should
        // be blocked since we already froze the order.
        sleep(1);
        $this->validateIsFresh($order, true);

        $gatewayName = $request->query('gateway');
        // Get the gateway to use.
        $gatewayManager = new GatewayManager($gatewayName);
        // Create a Payment model with the selected gateway.
        $payment = $this->createPayment($gatewayManager, $order);
        // Save the Gateway's request data in the Payment model.
        $payment->request = $gatewayManager->paymentRequest($payment, $request->all());

        $payment->save();

        // Dispatch event if we have a payment that is already successful.
        // Possible with "Free" payments.
        if ($payment->status === Payment::STATUS_SUCCESS) {
            event(new PaymentSuccessful($order));
        }

        // Send notifications if payment is successful and order is marked as payed.
        // If order is marked as something else, this might be an old Payment
        // being processed.
        if ($payment->status === Payment::STATUS_SUCCESS && $order->status === Order::STATUS_PAYED) {
            $gatewayManager->gateway->payment->load('order');
            $gatewayManager->gateway->sendApprovedNotification();
        }

        return $payment;
    }

    /**
     * Process a callback from the gateway.
     */
    public function gatewayCallback(Request $request, $gatewayName)
    {
        $gatewayManager = new GatewayManager($gatewayName);
        $gatewayManager->processCallback($request->all());

        return 'Prilov!';
    }

    protected function setVisibility(Collection $collection)
    {
        /* $collection->load([
            'order.user',
        ]);
        $collection->each(function ($payment) {
            $payment->order->user->makeVisible(['email', 'phone']);
            $payment->order->append(['due']);
        }); */
    }
}
