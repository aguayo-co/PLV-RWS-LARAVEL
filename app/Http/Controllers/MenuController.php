<?php

namespace App\Http\Controllers;

use App\Menu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MenuController extends AdminController
{
    protected $modelClass = Menu::class;
    public static $allowedWhereLike = ['slug'];

    protected function alterValidateData($data, Model $menu = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(array $data, ?Model $menu)
    {
        $required = !$menu ? 'required|' : '';
        $ignore = $menu ? ',' . $menu->id : '';
        return [
            'name' => $required . 'string|unique:menus,name' . $ignore,
            'slug' => 'string|unique:menus,slug' . $ignore,
        ];
    }
}
