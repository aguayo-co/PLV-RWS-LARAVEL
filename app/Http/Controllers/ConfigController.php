<?php

namespace App\Http\Controllers;

use App\Order;
use App\Sale;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConfigController extends BaseController
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        return [
            'payments' => [
                'minutes_until_canceled' => config('prilov.payments.minutes_until_canceled')
            ]
        ];
    }
}
