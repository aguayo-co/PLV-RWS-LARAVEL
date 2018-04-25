<?php

namespace App;

use App\Geoname;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'number', 'street', 'additional', 'commune',
    ];
    protected $hidden = [
        'geoname'
    ];
    protected $appends = [
        'commune', 'region', 'province'
    ];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function geoname()
    {
        return $this->belongsTo('App\Geoname', 'geonameid');
    }

    protected function getRegionAttribute($value)
    {
        $region = Geoname::where('feature_code', 'ADM1')
            ->where('admin1_code', $this->geoname->admin1_code)->first();
        return data_get($region, 'name');
    }

    protected function getProvinceAttribute($value)
    {
        $province = Geoname::where('feature_code', 'ADM2')
            ->where('admin2_code', $this->geoname->admin2_code)->first();
        return data_get($province, 'name');
    }

    protected function getCommuneAttribute($value)
    {
        return data_get($this->geoname, 'name');
    }

    protected function setCommuneAttribute($value)
    {
        $commune = Geoname::where('feature_code', 'ADM3')
            ->where('name', $value)->first();
        $this->attributes['geonameid'] = data_get($commune, 'geonameid');
    }

    /**
     * Custom binding to load a address.
     *
     * Check the user form the URL is the owner of the address.
     */
    public function resolveRouteBinding($value)
    {
        $user = request()->user;
        if (!$user) {
            return;
        }

        $address = parent::resolveRouteBinding($value);
        if (!$address) {
            return;
        }

        if ($user->id !== $address->user_id) {
            return;
        }

        return $address;
    }
}
