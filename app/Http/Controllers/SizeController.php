<?php

namespace App\Http\Controllers;

use App\Size;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SizeController extends Controller
{
    protected $modelClass = Size::class;

    protected function alterValidateData($data, Model $size = null)
    {
        # ID needed to validate it is not self-referenced.
        $data['id'] = $size ? $size->id : false;
        return $data;
    }

    protected function validationRules(array $data, ?Model $size)
    {
        $required = !$size ? 'required|' : '';
        $ignore = $size ? ',' . $size->id : '';
        $rules = [
            'name' => $required . 'string|unique:sizes,name' . $ignore,
            'parent_id' => [
                'integer',
                'different:id',
                Rule::exists('sizes', 'id')->where(function ($query) {
                    $query->whereNull('parent_id');
                }),
            ],
        ];

        // Custom rule needs message attached to it.
        // Can't use ValidationMessages for this one.
        if ($size) {
            $rules['parent_id'][] = function ($attribute, $value, $fail) use ($size) {
                if ($size->children()->count() && $value) {
                    return $fail(__('Esta talla tiene hijos. No pude tener un padre.'));
                }
            };

            if (!$size->products()->count()) {
                array_unshift($rules['parent_id'], 'nullable');
            }
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
            $query = $query->whereNull('parent_id');
            // Unless explicitly asked for a flat list, load children.
            if (!request()->get('flat')) {
                $query = $query->with(['children']);
            }
            return $query;
        };
    }

    public function show(Request $request, Model $size)
    {
        $size = parent::show($request, $size)->load(['children', 'parent']);
        return $size;
    }
}
