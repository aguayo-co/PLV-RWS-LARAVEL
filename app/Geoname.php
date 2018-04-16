<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Geoname extends Model
{
    protected $primaryKey = 'geonameid';
    public $incrementing = false;
}
