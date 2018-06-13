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
}
