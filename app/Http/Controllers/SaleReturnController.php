<?php

namespace App\Http\Controllers;

use App\SaleReturn;
use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SaleReturnController extends Controller
{
    protected $modelClass = SaleReturn::class;

    public static $allowedWhereIn = ['id', 'sale_id'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin')->only('show');
    }

    /**
     * When user is not admin, limit to current user returns.
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

    protected function validationRules(array $data, ?Model $return)
    {
        $validStatuses = [
            SaleReturn::STATUS_SHIPPED,
            SaleReturn::STATUS_DELIVERED,
            SaleReturn::STATUS_RECEIVED,
            SaleReturn::STATUS_ADMIN,
            SaleReturn::STATUS_COMPLETED,
            SaleReturn::STATUS_CANCELED,
        ];
        $minStatus = $return ? $return->status : min($validStatuses);
        // Status can only go back from DELIVERED to SHIPPED.
        // For any other status it can only go up.
        $minStatus = $minStatus === SaleReturn::STATUS_DELIVERED ? SaleReturn::STATUS_SHIPPED : $minStatus;

        $saleId = $return ? $return->sale->id : array_get($data, 'sale_id');
        $required = !$return ? 'required|' : '';
        return [
            'shipment_details' => [
                'array',
                $this->getCanSetShipmentDetailsRule($return),
            ],
            'reason' => [
                trim($required, '|'),
                'string',
                $this->getCanSetReasonRule($return),
            ],
            'sale_id' => [
                trim($required, '|'),
                'integer',
                'exists:sales,id',
                // Once a return has been created, sale can't be changed,
                // not even by admins. A new one has to be created instead.
                // And, only allow one return per Sale.
                $this->getSaleIdRule($return, $saleId),
            ],
            'products_ids' => [
                'bail',
                trim($required, '|'),
                $this->getCanAlterProducts($return),
                'array'
            ],
            'products_ids.*' => [
                Rule::exists('product_sale', 'product_id')->where(function ($query) use ($saleId) {
                    $query->where('sale_id', $saleId);
                }),
            ],
            'status' => [
                'bail',
                'integer',
                Rule::in($validStatuses),
                $this->getStatusRule($return),
                // Do not go back in status.
                'min:' . $minStatus,
            ],
        ];
    }

    protected function getCanAlterProducts($return)
    {
        return function ($attribute, $value, $fail) use ($return) {
            if ($return && $value) {
                return $fail(__('Can not modify products for existing return.'));
            }
        };
    }

    protected function getSaleIdRule($return, $saleId)
    {
        // Is this the Sale for the existing return.
        if ($return) {
            return Rule::in([$saleId]);
        }

        $user = auth()->user();
        // If it is an admin, allow creation of returns for orders that:
        //  - Have no returns created already.
        //  - Are already shipped
        //  - Have not been completed.
        $salesIdsQuery = Sale::where('status', '>', Sale::STATUS_SHIPPED)
            ->where('status', '<', Sale::STATUS_COMPLETED)
            ->whereDoesntHave('products', function ($query) {
                $query->whereNotNull('sale_return_id');
            });
        if ($user->hasRole('admin')) {
            return Rule::in($salesIdsQuery->pluck('id')->all());
        }

        // If it is not an admin, add one more restriction:
        //  - Sales that are from one of the user orders.
        $salesIds = $salesIdsQuery
            ->whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->pluck('id')->all();
        return Rule::in($salesIds);
    }

    /**
     * Rule that validates that a SaleReturn status is valid.
     */
    protected function getStatusRule($return)
    {
        return function ($attribute, $value, $fail) use ($return) {
            if (!$return) {
                return $fail(__('Can not set status on new returns.'));
            }

            $user = auth()->user();
            // Admins can set any status once return exists.
            if ($user->hasRole('admin')) {
                return;
            }

            $value = (int)$value;

            if ($value === SaleReturn::STATUS_CANCELED) {
                return $fail(__('Only an Admin can cancel a Return.'));
            }

            // Buyer can set two statuses.
            // Validate we have one of those.
            $buyerStatuses = [
                SaleReturn::STATUS_SHIPPED,
                SaleReturn::STATUS_DELIVERED,
            ];
            $buyer = $return->sales->first()->order->user;
            if ($user->is($buyer) && !in_array($value, $buyerStatuses, true)) {
                return $fail(__('validation.in', ['values' => implode(', ', $buyerStatuses)]));
            }

            // Seller can set three statuses.
            // Validate we have one of those.
            $sellerStatuses = [
                SaleReturn::STATUS_RECEIVED,
                SaleReturn::STATUS_ADMIN,
                SaleReturn::STATUS_COMPLETED,
            ];
            $seller = $return->sales->first()->user;
            if ($user->is($seller) && !in_array($value, $sellerStatuses, true)) {
                return $fail(__('validation.in', ['values' => implode(', ', $sellerStatuses)]));
            }
        };
    }

    /**
     * Rule that validates that Shipment Details can be set.
     */
    protected function getCanSetShipmentDetailsRule($return)
    {
        return function ($attribute, $value, $fail) use ($return) {
            if (!$return) {
                return $fail(__('No se puede agregar esta información durante la creación.'));
            }

            $user = auth()->user();
            $seller = $return->sales->first()->user;
            if ($user->is($seller)) {
                return $fail(__('Usuario no tiene permiso para modificar esta información.'));
            }

            if (SaleReturn::STATUS_RECEIVED < $return->status) {
                return $fail(__('Información ya no se puede modificar.'));
            }
        };
    }

    protected function getCanSetReasonRule($return)
    {
        return function ($attribute, $value, $fail) use ($return) {
            if ($return) {
                return $fail(__('Información ya no se puede modificar.'));
            }
        };
    }

    protected function alterFillData($data, Model $return = null)
    {
        if (!$return) {
            $data['status'] = SaleReturn::STATUS_PENDING;
        }

        if ($productsIds = array_get($data, 'products_ids')) {
            $saleId = $return ? $return->sale->id : array_get($data, 'sale_id');
            $data['products_ids'] = ['sale_id' => $saleId, 'products_ids' => $productsIds];
        }

        unset($data['sale_id']);
        return parent::alterFillData($data, $return);
    }
}
