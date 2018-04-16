<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CategoryController extends AdminController
{
    protected $modelClass = Category::class;
    public static $allowedWhereIn = ['id', 'parent_id'];
    public static $allowedWhereLike = ['slug'];

    protected function alterValidateData($data, Model $category = null)
    {
        # ID needed to validate it is not self-referenced.
        $data['id'] = $category ? $category->id : false;
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(array $data, ?Model $category)
    {
        $required = !$category ? 'required|' : '';
        $ignore = $category ? ',' . $category->id : '';
        $rules = [
            'name' => [
                trim($required, '|'),
                'string',
                // We want unique items per category or subcategory.
                'unique_with:categories,parent_id' . $ignore,
            ],
            'slug' => [
                'string',
                // We want unique items per category or subcategory.
                'unique_with:categories,parent_id' . $ignore,
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'different:id',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('parent_id');
                }),
            ],
        ];

        // Custom rule needs message attached to it.
        // Can't use ValidationMessages for this one.
        if ($category) {
            $rules['parent_id'][] = function ($attribute, $value, $fail) use ($category) {
                if (count($category->children) && $value) {
                    return $fail(__('Esta categoría tiene hijos. No pude tener un padre.'));
                }
            };
        };

        return $rules;
    }

    protected function validationMessages()
    {
        return ['parent_id.exists' => __('validation.not_in')];
    }

    protected function alterIndexQuery()
    {
        return function ($query) {
            $query = $query->with(['children']);
            // Unless we have a filter
            if (!request()->query('filter')) {
                $query = $query->whereNull('parent_id');
            }
            return $query;
        };
    }

    public function show(Request $request, Model $category)
    {
        $category = parent::show($request, $category)->load(['children']);
        return $category;
    }

    public function showSubcategory(Request $request, Model $category, Model $subcategory)
    {
        $subcategory = parent::show($request, $subcategory)->load(['parent']);
        return $subcategory;
    }
}
