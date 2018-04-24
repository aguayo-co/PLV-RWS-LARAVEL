<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Geoname extends Model
{
    protected $primaryKey = 'geonameid';
    public $incrementing = false;

    public function chilexpressGeodata()
    {
        return $this->hasOne('App\ChilexpressGeodata', 'geonameid');
    }
}
