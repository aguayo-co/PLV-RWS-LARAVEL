<?php

namespace App\Http\Controllers;

use App\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
                $this->getCanSetShippingDetailsRule($sale),
            ],
            'status' => [
                'bail',
                'integer',
                Rule::in($validStatuses),
                $this->getStatusRule($sale),
                // Do not go back in status.
                'min:' . $sale->status,
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
     * Rule that validates that a Sale status is valid.
     */
    protected function getCanSetShippingDetailsRule($sale)
    {
        return function ($attribute, $value, $fail) use ($sale) {
            // Order needs to be payed.
            if ($sale->status < Sale::STATUS_PAYED) {
                return $fail(__('La orden no ha sido pagada.'));
            }
            // Order shipped already.
            if (Sale::STATUS_RECEIVED < $sale->status) {
                return $fail(__('Información ya no se puede modificar.'));
            }
        };
    }

    public function index(Request $request)
    {
        $pagination = parent::index($request);
        $sales = $pagination->getCollection();
        $sales = $this->setVisibility($sales);
        $pagination->setCollection($sales);
        return $pagination;
    }

    public function postStore(Request $request, Model $sale)
    {
        return $this->setVisibility(parent::postStore($request, $sale));
    }

    public function postUpdate(Request $request, Model $sale)
    {
        return $this->setVisibility(parent::postUpdate($request, $sale));
    }

    public function show(Request $request, Model $sale)
    {
        return $this->setVisibility(parent::show($request, $sale));
    }

    protected function setVisibility($data)
    {
        $data = $data->load(['order.user']);
        $this->hideOrderSales($data);
        return $data;
    }

    protected function hideOrderSales($data)
    {
        if (! $data instanceof Collection) {
            $data = new Collection([$data]);
        }
        $data->each(function ($item) {
            $item->order->makeHidden('sales');
        });
    }
}
