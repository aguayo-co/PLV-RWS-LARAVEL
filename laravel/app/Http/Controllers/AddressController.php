<?php

namespace App\Http\Controllers;

use App\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public $modelClass = Address::class;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin:user');
    }

    protected function validationRules(?Model $model)
    {
        return [
            'address' => 'required|string',
            'region' => 'required|string',
            'zone' => 'required|string',
        ];
    }

    /**
     * Alter data to be passed to fill method.
     *
     * @param  array  $data
     * @return array
     */
    public function alterFillData($data)
    {
        $user = request()->route('user');
        $data['user_id'] = $user->id;
        return $data;
    }

    /**
     * Display the specified resource.
     *
     * @param  Illuminate\Database\Eloquent\Model $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Model $user)
    {
        return $user->addresses()->get();
    }
}