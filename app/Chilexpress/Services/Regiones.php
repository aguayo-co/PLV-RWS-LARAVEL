<?php

namespace App\Chilexpress\Services;

use App\ChilexpressGeodata;
use App\Geoname;
use Illuminate\Support\Facades\Log;

/**
 * Clase para calculo de tarifa de chilexpress
 */
trait Regiones
{
    /**
     * Retorna las regiones de Chile.
     */
    public function regiones()
    {
        if ($cached = cache('chilexpress.regiones')) {
            return $cached;
        };

        $route = "ConsultarRegiones";

        $method = 'reqObtenerRegion';

        $clientOptions = array(
            'login'    => "UsrTestServicios",
            'password' => "U$\$vr2\$tS2T",
            'cache_wsdl' => WSDL_CACHE_NONE,
            'exceptions' => 0,
            'stream_context' => stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true //can fiddle with this one.
                )
            ))
        );

        $client = new \SoapClient(dirname(__DIR__) . "/wsdl/WSDL_GeoReferencia_QA.wsdl", $clientOptions);
        $headerBody = array(
            'transaccion' => array(
                'fechaHora'            => date('Y-m-d\TH:i:s.Z\Z', time()),
                'idTransaccionNegocio' => '0',
                'sistema'              => 'TEST',
                'usuario'              => 'TEST',
                'oficinaCaja'          => 'TEST',
            )
        );

        $header = new \SoapHeader("http://www.chilexpress.cl/CorpGR/", 'headerRequest', $headerBody);
        $client->__setSoapHeaders($header);

        $result = $client->__soapCall($route, [ $route => [ $method => [] ] ]);

        if (is_soap_fault($result)) {
            return null;
        }

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
