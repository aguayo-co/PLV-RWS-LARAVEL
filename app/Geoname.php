<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para almacenamiento de regiones basado en datos de http://geonames.org/
 */
class Geoname extends Model
{
    protected $primaryKey = 'geonameid';
    public $incrementing = false;

    public function chilexpressGeodata()
    {
        return $this->hasOne('App\ChilexpressGeodata', 'geonameid');
    }
}
