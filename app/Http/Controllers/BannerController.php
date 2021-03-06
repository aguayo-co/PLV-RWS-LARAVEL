<?php

namespace App\Http\Controllers;

use App\Banner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BannerController extends AdminController
{
    protected $modelClass = Banner::class;
    public static $allowedWhereLike = ['slug', 'name'];
    public static $defaultOrderBy = ['slug' => 'asc'];

    protected function alterValidateData($data, Model $banner = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(array $data, ?Model $banner)
    {
        $required = !$banner ? 'required|' : '';
        $ignore = $banner ? ',' . $banner->id : '';
        return [
            'name' => $required . 'string|unique:banners,name' . $ignore,
            'slug' => 'string|unique:banners,slug' . $ignore,
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'image' => 'nullable|image',
            'button_text' => 'nullable|string',
            'url' => 'nullable|string',
        ];
    }
}
