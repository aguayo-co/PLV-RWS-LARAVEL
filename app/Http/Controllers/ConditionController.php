<?php

namespace App\Http\Controllers;

use App\Condition;
use Illuminate\Database\Eloquent\Model;

class ConditionController extends AdminController
{
    protected $modelClass = Condition::class;
    public static $allowedWhereLike = ['slug'];

    protected function alterValidateData($data, Model $condition = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(array $data, ?Model $condition)
    {
        $required = !$condition ? 'required|' : '';
        $ignore = $condition ? ',' . $condition->id : '';
        return [
            'name' => $required . 'string|unique:conditions,name' . $ignore,
            'slug' => 'string|unique:conditions,slug' . $ignore,
        ];
    }
}
