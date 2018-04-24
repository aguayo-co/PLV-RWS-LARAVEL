<?php

namespace App\Chilexpress\Services;

use App\ChilexpressGeodata;
use App\Geoname;
use Illuminate\Support\Facades\Log;

/**
 * Clase para calculo de tarifa de chilexpress
 */
trait Comunas
{
    /**
     * Retorna las comunas de Chile con cobertura.
     */
    public function comunas()
    {
        $route = "ConsultarCoberturas";

        $method = 'reqObtenerCobertura';
        $data = [
            'CodTipoCobertura' => 3,
            'CodRegion' => 99,
        ];

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

        $result = $client->__soapCall($route, [ $route => [ $method => $data ] ]);

        if (is_soap_fault($result)) {
            return null;
        }

        return $result->respObtenerCobertura->Coberturas;
    }

    /**
     * Maps comuna information form Chilexpress to Geonames.
     */
    public function persistComunas()
    {
        $geonames = Geoname::where('country_code', 'CL')->where('feature_code', 'ADM3')->get();
        $regiones = ChilexpressGeodata::where('type', 'region')->get();
        $comunas = collect($this->comunas());

        $savedGeonames = collect();

        foreach ($regiones as $region) {
            foreach ($comunas->where('CodRegion', $region->region_cod) as $comuna) {
                $comunaFound = false;
                $comunaSlug = str_replace('-', '', str_slug($comuna->GlsComuna));
                foreach ($geonames->where('admin1_code', $region->geoname->admin1_code) as $geoname) {
                    $geonameSlug = str_replace('-', '', str_slug($geoname->name));
                    if (strpos($geonameSlug, $comunaSlug)  === false && strpos($comunaSlug, $geonameSlug) === false) {
                        continue;
                    }

                    $comunaFound = true;
                    $savedGeonames->push($geoname);
                    $chilexpressGeodata = ChilexpressGeodata::firstOrNew(['geonameid' => $geoname->geonameid]);

                    if (!$chilexpressGeodata->comuna_cod === $comuna->CodComuna) {
                        Log::error("Duplicado en Geonames ({$geoname->geonameid}) para comuna de Chilexpress: {$comuna->GlsComuna}:{$comuna->CodComuna} - {$chilexpressGeodata->name}:{$chilexpressGeodata->comuna_cod}");
                    }

                    $chilexpressGeodata->name = $comuna->GlsComuna;
                    $chilexpressGeodata->type = 'comuna';
                    $chilexpressGeodata->region_cod = $comuna->CodRegion;
                    $chilexpressGeodata->comuna_cod = $comuna->CodComuna;
                    $chilexpressGeodata->comuna_cod_ine = $comuna->CodComunaIne;
                    $chilexpressGeodata->Save();
                }
                if (!$comunaFound) {
                    Log::error("Sin equivalencia en Geonames para comuna de Chilexpress: {$comuna->GlsComuna} - {$comuna->CodComuna}");
                }
            }
        }

        $notSavedGeonames = $geonames->diff($savedGeonames);
        if ($notSavedGeonames->count() > 0) {
            foreach ($notSavedGeonames as $geoname) {
                Log::error("Sin equivalencia en Chilexpress para comuna de Geonames: {$geoname->name} - {$geoname->admin2_code}");
            }
        }


    }
}
