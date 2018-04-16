<?php

namespace App\Http\Controllers;

use App\Group;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class GroupController extends AdminController
{
    protected $modelClass = Group::class;
    public static $allowedWhereLike = ['slug'];

    protected function alterValidateData($data, Model $group = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(array $data, ?Model $group)
    {
        $required = !$group ? 'required|' : '';
        $ignore = $group ? ',' . $group->id : '';
        return [
            'name' => $required . 'string|unique:groups,name' . $ignore,
            'slug' => 'string|unique:groups,slug' . $ignore,
        ];
    }
}
