<?php

namespace App\Http\Controllers;

use App\Brand;
use Illuminate\Database\Eloquent\Model;

class BrandController extends AdminController
{
    protected $modelClass = Brand::class;
    public static $allowedWhereLike = ['slug'];

    protected function alterValidateData($data, Model $brand = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(array $data, ?Model $brand)
    {
        $required = !$brand ? 'required|' : '';
        $ignore = $brand ? ',' . $brand->id : '';
        return [
            'name' => $required . 'string|unique:brands,name' . $ignore,
            'slug' => 'string|unique:brands,slug' . $ignore,
            'url' => 'nullable|string',
        ];
    }
}
