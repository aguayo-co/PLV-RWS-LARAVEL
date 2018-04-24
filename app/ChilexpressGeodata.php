<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChilexpressGeodata extends Model
{
    protected $table = 'chilexpress_geodata';
    protected $primaryKey = 'geonameid';
    public $incrementing = false;

    public $fillable = ['geonameid'];
    public $with = ['geoname'];

    public function geoname()
    {
        return $this->belongsTo('App\Geoname', 'geonameid');
    }
}
