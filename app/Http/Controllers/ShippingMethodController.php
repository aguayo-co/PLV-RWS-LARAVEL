<?php

namespace App\Http\Controllers;

use App\ShippingMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ShippingMethodController extends AdminController
{
    protected $modelClass = ShippingMethod::class;
    public static $allowedWhereLike = ['slug'];

    protected function alterValidateData($data, Model $shippingMethod = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(array $data, ?Model $shippingMethod)
    {
        $required = !$shippingMethod ? 'required|' : '';
        $ignore = $shippingMethod ? ',' . $shippingMethod->id : '';
        return [
            'name' => $required . 'string|unique:shipping_methods,name' . $ignore,
            'slug' => 'string|unique:shipping_methods,slug' . $ignore,
            'description_seller' => $required . 'string|max:10000',
            'description_buyer' => $required . 'string|max:10000',
        ];
    }
}
