<?php

namespace App;

use App\Traits\DateSerializeFormat;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para almacenamiento de regiones basado en datos de http://geonames.org/
 */
class Geoname extends Model
{
    use DateSerializeFormat;

    protected $primaryKey = 'geonameid';
    public $incrementing = false;

    public function chilexpressGeodata()
    {
        return $this->hasOne('App\ChilexpressGeodata', 'geonameid');
    }

    public function admin1()
    {
        return $this->belongsTo('App\Geoname', 'admin1_code', 'admin1_code')->whereNull('admin2_code');
    }

    public function admin2()
    {
        return $this->belongsTo('App\Geoname', 'admin2_code', 'admin2_code')->whereNull('admin3_code');
    }
}
