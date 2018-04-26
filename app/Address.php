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
        'user_id', 'number', 'street', 'additional', 'geonameid',
    ];
    protected $hidden = [
        'geoname'
    ];
    protected $appends = [
        'commune', 'region', 'province'
    ];

    public static function boot()
    {
        parent::boot();
        self::created(function ($address) {
            if (!$address->user->favorite_address_id) {
                $address->user->favorite_address_id = $address->id;
                $address->user->save();
            }
        });
    }

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

    public function chilexpressGeodata()
    {
        return $this->belongsTo('App\ChilexpressGeodata', 'geonameid');
    }

    protected function getRegionAttribute()
    {
        $region = Geoname::where('feature_code', 'ADM1')
            ->where('admin1_code', $this->geoname->admin1_code)->first();
        return data_get($region, 'name');
    }

    protected function getProvinceAttribute()
    {
        $province = Geoname::where('feature_code', 'ADM2')
            ->where('admin2_code', $this->geoname->admin2_code)->first();
        return data_get($province, 'name');
    }

    protected function getCommuneAttribute()
    {
        return data_get($this->geoname, 'name');
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
