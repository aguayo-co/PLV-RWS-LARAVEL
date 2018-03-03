<?php

namespace App\Http\Controllers;

use App\Status;
use Illuminate\Database\Eloquent\Model;

class StatusController extends Controller
{
    public $modelClass = Status::class;

    public function alterValidateData($data)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $status)
    {
        return [
            'name' => 'required|string|unique:statuses',
            'slug' => 'required|string|unique:statuses',
        ];
    }
}
