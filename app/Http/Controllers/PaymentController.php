<?php

namespace App\Http\Controllers;

use App\Events\PaymentStarted;
use App\Events\PaymentSuccessful;
use App\Gateways\Gateway;
use App\Http\Controllers\Order\CouponRules;
use App\Http\Traits\CurrentUserOrder;
use App\Order;
use App\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use CouponRules;
    use CurrentUserOrder;

    protected $modelClass = Payment::class;

    public static $allowedWhereIn = ['id', 'gateway'];
    public static $allowedWhereBetween = ['status'];

    public function __construct()
    {
        parent::__construct();

        $this->middleware('role:admin')->only('index');
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
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid total value.');
        }

        switch ($order->status) {
            case Order::STATUS_TRANSACTION:
                $this->validateTransactionOrder($order);
                break;

            case Order::STATUS_SHOPPING_CART:
                $this->validateShoppingCartOrder($order);
                break;

            default:
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Order can not proceed to Check Out.');
        }
    }

    protected function validateTransactionOrder($order)
    {
        if ($order->sales->count()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Transaction order can not have sales.');
        }

        if ($order->coupon) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Transaction order can not have coupon.');
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
            abort(Response::HTTP_FAILED_DEPENDENCY, 'No products in shopping cart.');
        }

        if ($order->products->where('saleable', false)->count()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Some products are not available anymore.');
        }

        if ($order->sales->where('shipping_method_id', null)->count()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Some sales do not have a ShippingMethod.');
        }

        if (!data_get($order, 'shipping_information.phone')) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Order needs shipping phone.');
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
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Order needs shipping address.');
            }

            if ($sale->is_chilexpress && !$sale->allow_chilexpress) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Address not covered by Chilexpress.');
            }
        });
    }

    /**
     * Create a new payment for the given order, with the given gateway.
     */
    protected function getPayment(Gateway $gateway, Model $order)
    {
        $payment = new Payment();
        $payment->gateway = $gateway->getName();
        $payment->status = Payment::STATUS_PENDING;
        $payment->order_id = $order->id;

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
        $this->validateOrderCanCheckout($order);

        $gatewayName = $request->query('gateway');

        if ($order->due > 0 && $gatewayName === 'free') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid gateway.');
        }

        // Get the gateway to use.
        $gateway = new Gateway($gatewayName);
        // Create a Payment model with the selected gateway.
        $payment = $this->getPayment($gateway, $order);
        // Save the Gateway's request data in the Payment model.
        $payment->request = $gateway->paymentRequest($payment, $request->all());
        $payment->save();

        event(new PaymentStarted($order));

        // Dispatch event if we have a payment that is already successful.
        // Possible with "Free" payments.
        if ($payment->status === Payment::STATUS_SUCCESS) {
            event(new PaymentSuccessful($order));
        }

        return $payment;
    }

    /**
     * Process a callback from the gateway.
     */
    public function gatewayCallback(Request $request, $gateway)
    {
        DB::transaction(function () use ($request, $gateway) {
            $gateway = new Gateway($gateway);
            $gateway->processCallback($request->all());
        });

        return 'Prilov!';
    }

    protected function setVisibility(Collection $collection)
    {
        $collection->load([
            'order.user',
        ]);
    }
}
