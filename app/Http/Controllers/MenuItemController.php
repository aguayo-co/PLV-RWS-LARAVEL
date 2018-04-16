<?php

namespace App\Http\Controllers;

use App\Rules\EmptyWith;
use App\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MenuItemController extends AdminController
{
    protected $modelClass = MenuItem::class;

    protected function alterValidateData($data, Model $menuItem = null)
    {
        # ID needed to validate it is not self-referenced.
        $data['id'] = $menuItem ? $menuItem->id : false;
        return $data;
    }

    protected function validationRules(array $data, ?Model $menuItem)
    {
        $required = !$menuItem ? 'required|' : '';
        $requiredMenuId = !$menuItem ? 'required_without:parent_id|' : '';
        $requiredParentId = !$menuItem ? 'required_without:menu_id|' : '';
        return [
            'name' => $required . 'string',
            'url' => 'nullable|string',
            'icon' => 'nullable|string',
            'parent_id' => $requiredParentId . 'integer|empty_with:menu_id|exists:menu_items,id|different:id',
            'menu_id' => $requiredMenuId . 'integer|empty_with:parent_id|exists:menus,id',
        ];
    }
}
