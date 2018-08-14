<?php

namespace App;

use App\Traits\DateSerializeFormat;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use DateSerializeFormat;

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
        'commune', 'region', 'province', 'can_admit_chilexpress', 'can_deliver_chilexpress'
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
        return $this->belongsTo('App\User')->withTrashed();
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
        if (!$this->geoname) {
            return null;
        }
        $region = Geoname::where('feature_code', 'ADM1')
            ->where('admin1_code', $this->geoname->admin1_code)->first();
        return data_get($region, 'name');
    }

    protected function getProvinceAttribute()
    {
        if (!$this->geoname) {
            return null;
        }
        $province = Geoname::where('feature_code', 'ADM2')
            ->where('admin2_code', $this->geoname->admin2_code)->first();
        return data_get($province, 'name');
    }

    protected function getCommuneAttribute()
    {
        return data_get($this->geoname, 'name');
    }

    protected function getCanDeliverChilexpressAttribute()
    {
        $coverage = data_get($this->chilexpressGeodata, 'coverage_type');
        // Deny if this comuna only admits or has no coverage.
        if (!$coverage || $coverage === 1) {
            return false;
        }
        return true;
    }

    protected function getCanAdmitChilexpressAttribute()
    {
        $coverage = data_get($this->chilexpressGeodata, 'coverage_type');
        // Deny if this comuna only delivers or has no coverage.
        if (!$coverage || $coverage === 2) {
            return false;
        }
        return true;
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
