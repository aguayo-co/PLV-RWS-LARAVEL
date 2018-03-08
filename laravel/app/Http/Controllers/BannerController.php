<?php

namespace App\Http\Controllers;

use App\Banner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    protected $modelClass = Banner::class;

    protected function alterValidateData($data, Model $banner = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $banner)
    {
        $required = !$banner ? 'required|' : '';
        $ignore = $banner ? ',' . $banner->id : '';
        return [
            'name' => $required . 'string|unique:banners,name' . $ignore,
            'slug' => 'string|unique:banners,slug' . $ignore,
            'title' => $required . 'string',
            'subtitle' => $required . 'string',
            'image' => 'nullable|image',
            'button_text' => 'nullable|string',
            'url' => $required . 'string',
        ];
    }
}
