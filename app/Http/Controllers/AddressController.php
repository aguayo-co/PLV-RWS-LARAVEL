<?php

namespace App\Http\Controllers;

use App\Address;
use App\Geoname;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    protected $modelClass = Address::class;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin')->except('regions');
    }

    protected function validationRules(array $data, ?Model $address)
    {
        $required = !$address ? 'required|' : '';

        return [
            'number' => $required . 'string',
            'street' => $required . 'string',
            'additional' => 'nullable|string',
            'commune' => [
                trim($required, '|'),
                'string',
                Rule::exists('geonames', 'name')->where(function ($query) {
                    $query->where('country_code', 'CL')->where('feature_code', 'ADM3');
                }),
            ]
        ];
    }

    /**
     * Alter data to be passed to fill method.
     *
     * @param  array  $data
     * @return array
     */
    protected function alterFillData($data, Model $address = null)
    {
        // Remove 'user_id' from $data.
        array_forget($data, 'user_id');
        if (!$address) {
            $user = request()->route('user');
            $data['user_id'] = $user->id;
        }

        array_forget($data, 'geonameid');
        $commune = data_get($data, 'commune');
        if ($commune) {
            $geoname = Geoname::where('country_code', 'CL')->where('feature_code', 'ADM3')
                ->where('name', $commune)->first();
            $data['geonameid'] = data_get($geoname, 'geonameid');
        }

        return $data;
    }

    /**
     * Return a Closure that modifies the index query.
     * The closure receives the $query as a parameter.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        return function ($query) {
            $user = request()->user;
            return $query->where('user_id', $user->id);
        };
    }

    /**
     * This route get two models, $user and $address.
     * Only $address is passed as a parameter to the parent,
     * we need to retrieve the $address from the request and pass it
     * to the parent.
     */
    public function show(Request $request, Model $user)
    {
        return parent::show($request, $request->route()->parameters['address']);
    }

    /**
     * This route get two models, $user and $address.
     * Only $address is passed as a parameter to the parent,
     * we need to retrieve the $address from the request and pass it
     * to the parent.
     */
    public function ownerDelete(Request $request, Model $user)
    {
        return parent::ownerDelete($request, $request->route()->parameters['address']);
    }

    /**
     * This route get two models, $user and $address.
     * Only $address is passed as a parameter to the parent,
     * we need to retrieve the $address from the request and pass it
     * to the parent.
     */
    public function delete(Request $request, Model $user)
    {
        return parent::delete($request, $request->route()->parameters['address']);
    }

    /**
     * This route get two models, $user and $address.
     * Only $address is passed as a parameter to the parent,
     * we need to retrieve the $address from the request and pass it
     * to the parent.
     */
    public function update(Request $request, Model $user)
    {
        return parent::update($request, $request->route()->parameters['address']);
    }

    public function regions(Request $request)
    {
        $admin1 = Geoname::where('country_code', 'CL')->where('feature_code', 'ADM1')
            ->select('admin1_code', 'name', 'geonameid')->get();
        $admin2 = Geoname::where('country_code', 'CL')->where('feature_code', 'ADM2')
            ->whereIn('admin1_code', $admin1->pluck('admin1_code')->all())
            ->select('admin1_code', 'admin2_code', 'name', 'geonameid')->get();
        $admin3 = Geoname::where('country_code', 'CL')->where('feature_code', 'ADM3')
            ->whereIn('admin2_code', $admin2->pluck('admin2_code')->all())
            ->select('admin1_code', 'admin2_code', 'admin3_code', 'name', 'geonameid')->get();

        $groupedAdmin3 = $admin3->groupBy('admin2_code');
        foreach ($admin2 as &$adm2) {
            $adm2->children = $groupedAdmin3[$adm2->admin2_code]->keyBy('name');
        }

        $groupedAdmin2 = $admin2->groupBy('admin1_code');
        foreach ($admin1 as &$adm1) {
            $adm1->children = $groupedAdmin2[$adm1->admin1_code]->keyBy('name');
        }

        return $admin1->keyBy('name');
    }

    protected function setVisibility(Collection $collection)
    {
        $collection->load([
            'chilexpressGeodata',
        ]);
    }
}
