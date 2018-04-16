<?php

namespace App\Http\Controllers;

use App\Campaign;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class CampaignController extends AdminController
{
    protected $modelClass = Campaign::class;
    public static $allowedWhereLike = ['slug'];

    protected function alterValidateData($data, Model $campaign = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(array $data, ?Model $campaign)
    {
        $required = !$campaign ? 'required|' : '';
        $ignore = $campaign ? ',' . $campaign->id : '';
        return [
            'name' => $required . 'string|unique:campaigns,name' . $ignore,
            'slug' => 'string|unique:campaigns,slug' . $ignore,
        ];
    }
}
