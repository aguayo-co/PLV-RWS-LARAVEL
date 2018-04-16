<?php

namespace App\Http\Controllers;

use App\Gateways\Gateway;
use App\Http\Controllers\Order\CouponRules;
use App\Http\Traits\CurrentUserOrder;
use App\Order;
use App\Payment;
use App\Product;
use App\Sale;
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

    public function __construct()
    {
        parent::__construct();

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
        return [];
    }

    /**
     * Validate that an order can be sent to Checkout.
     */
    protected function validateOrderCanCheckout($order)
    {
        if ($order->coupon) {
            Validator::make(
                ['coupon_code' => $order->coupon->code],
                ['coupon_code' => $this->getCouponRules($order)]
            )->validate();
        }

        if ($order->status !== Order::STATUS_SHOPPING_CART) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Order is not in Shopping Cart.');
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

        if (!data_get($order, 'shipping_information.address')) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Order needs shipping address.');
        }

        if (!data_get($order, 'shipping_information.phone')) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Order needs shipping phone.');
        }
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
        $payment->save();

        return $payment;
    }

    /**
     * Create a new payment for the current user.
     */
    public function store(Request $request)
    {
        $order = $this->currentUserOrder();

        return $this->generatePayment($request, $order);
    }

    /**
     * Create a new payment for give order.
     */
    public function generatePayment(Request $request, Order $order)
    {
        $this->validateOrderCanCheckout($order);

        // Get the gateway to use.
        $gateway = new Gateway($request->query('gateway'));
        // Create a Payment model with the selected gateway.
        $payment = $this->getPayment($gateway, $order);
        // Save the Gateway's request data in the Payment model.
        $payment->request = $gateway->paymentRequest($payment, $request->all());
        $payment->save();

        $order->status = Order::STATUS_PAYMENT;
        DB::transaction(function () use ($order) {
            $order->save();
            foreach ($order->sales as $sale) {
                $sale->status = Sale::STATUS_PAYMENT;
                $sale->save();
            }
            foreach ($order->products as $product) {
                $product->status = Product::STATUS_PAYMENT;
                $product->save();
            }
        });

        return $payment;
    }

    /**
     * Mark Order and its Sales as Payed.
     */
    public function approveOrder($order)
    {
        // We want to fire events.
        foreach ($order->sales as $sale) {
            $sale->status = Sale::STATUS_PAYED;
            $sale->save();
        }
        foreach ($order->products as $product) {
            $product->status = Product::STATUS_SOLD;
            $product->save();
        }

        $order->status = Order::STATUS_PAYED;
        $order->save();
    }

    /**
     * Process a callback from the gateway.
     */
    public function gatewayCallback(Request $request, $gateway)
    {
        DB::transaction(function () use ($request, $gateway) {
            $gateway = new Gateway($gateway);
            $payment = $gateway->processCallback($request->all());

            if ($payment->status === Payment::STATUS_SUCCESS) {
                $this->approveOrder($payment->order);
            }
        });

        return 'Prilov!';
    }
}
