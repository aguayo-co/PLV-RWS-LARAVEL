<?php

namespace App;

use App\Traits\DateSerializeFormat;
use Illuminate\Database\Eloquent\Model;

class ChilexpressGeodata extends Model
{
    use DateSerializeFormat;

    protected $table = 'chilexpress_geodata';
    protected $primaryKey = 'geonameid';
    public $incrementing = false;

    public $fillable = ['geonameid'];

    public function geoname()
    {
        return $this->belongsTo('App\Geoname', 'geonameid');
    }
}
