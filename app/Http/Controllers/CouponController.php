<?php

namespace App\Http\Controllers;

use App\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    protected $modelClass = Coupon::class;

    public static $allowedWhereIn = ['id', 'first_purchase_only', 'discount_value', 'discount_type'];
    public static $allowedWhereBetween = ['minimum_price', 'minimum_commission'];
    public static $allowedWhereDates = ['created_at', 'updated_at', 'valid_from', 'valid_date'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('role:admin');
    }

    protected function validationRules(array $data, ?Model $coupon)
    {
        $required = !$coupon ? 'required|' : '';
        $discountType = data_get($data, 'discount_type', data_get($coupon, 'discount_type'));
        $discountValueMax = $discountType === '%' ? 100 : '9999999';
        return [
            'description' => $required . 'string|max:10000',
            'code' =>  $required . 'string',
            'valid_from' => 'date|required_with:valid_to',
            'valid_to' => 'date|required_with:valid_from|after:valid_from',
            'minimum_price' => 'integer|between:0,9999999',
            'minimum_commission' => 'numeric|between:0,100',
            'first_purchase_only' => 'boolean',
            'discount_type' => $required . 'in:%,$',
            'discount_value' => $required . 'integer|between:0,' . $discountValueMax,
            'status' => ['integer', Rule::in(Coupon::getStatuses())],
            'brands_ids' => 'array',
            'brands_ids.*' => 'integer|exists:brands,id',
            'campaign_ids' => 'array',
            'campaign_ids.*' => 'integer|exists:campaigns,id',
        ];
    }
}
