<?php

namespace App\Chilexpress;

use App\ChilexpressGeodata;

class Address
{
    public $address;
    public $region;
    public $comuna;

    /**
     * Generate a new Address with data form Geonames table.
     * $region and $comuna must be valid Geonames.
     */
    public function __construct($address, $region, $comuna)
    {
        $this->address = $address;

        $this->region = ChilexpressGeodata::whereHas('geoname', function ($query) use ($region) {
            $query->where('feature_code', 'ADM1')->where('name', $region);
        })->firstOrFail();

        $this->comuna = ChilexpressGeodata::whereHas('geoname', function ($query) use ($comuna) {
            $query->where('feature_code', 'ADM3')->where('name', $comuna);
        })->where('region_cod', $this->region->region_cod)->firstOrFail();
    }
}
