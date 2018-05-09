<?php

namespace App\Chilexpress\Services;

use App\ChilexpressGeodata;
use App\Geoname;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

trait Regiones
{
    /**
     * Return Regions in Chile.
     */
    public function regiones()
    {
        if ($cached = cache('chilexpress.regiones')) {
            return $cached;
        };

        $route = "ConsultarRegiones";

        $method = 'reqObtenerRegion';

        $clientOptions = array(
            'exceptions' => true,
        );

        switch (App::environment()) {
            case 'production':
                $client = new \SoapClient('http://ws.ssichilexpress.cl/GeoReferencia?wsdl', $clientOptions);
                break;

            default:
                $client = new \SoapClient('http://qaws.ssichilexpress.cl/GeoReferencia?wsdl', $clientOptions);
                $client->__setLocation('http://qaws.ssichilexpress.cl/GeoReferencia');
        }

        $result = $client->__soapCall($route, [ $route => [ $method => [] ] ]);

        $regiones = $result->respObtenerRegion->Regiones;

        cache(['chilexpress.regiones' => $regiones], now()->addSeconds(3600));

        return $regiones;
    }

    /**
     * Maps region information form Chilexpress to Geonames.
     */
    public function persistRegiones()
    {
        $geonames = Geoname::where('country_code', 'CL')->where('feature_code', 'ADM1')->get();
        foreach ($this->regiones() as $region) {
            $regionFound = false;
            $regionSlug = str_replace('-', '', str_slug($region->GlsRegion));
            foreach ($geonames as $geoname) {
                $geonameSlug = str_replace('-', '', str_slug($geoname->name));
                if (strpos($geonameSlug, $regionSlug)  === false && strpos($regionSlug, $geonameSlug) === false) {
                    continue;
                }

                $regionFound = true;
                $chilexpressGeodata = ChilexpressGeodata::firstOrNew(['geonameid' => $geoname->geonameid]);
                $chilexpressGeodata->name = $region->GlsRegion;
                $chilexpressGeodata->type = 'region';
                $chilexpressGeodata->region_cod = $region->idRegion;
                $chilexpressGeodata->save();
            }
            if (!$regionFound) {
                Log::error("Sin equivalencia en Geonames para región de Chilexpress: {$region->GlsRegion} - {$region->idRegion}");
            }
        }
    }
}
