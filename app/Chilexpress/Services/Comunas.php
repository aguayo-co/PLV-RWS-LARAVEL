<?php

namespace App\Chilexpress\Services;

use App\ChilexpressGeodata;
use App\Geoname;
use Illuminate\Support\Facades\Log;

trait Comunas
{
    /**
     * Return Communes with coverage by Chilexpress.
     */
    public function comunas($coverageType = 3)
    {

        if ($cached = cache('chilexpress.comunas.' . $coverageType)) {
            return $cached;
        };

        $route = "ConsultarCoberturas";

        $method = 'reqObtenerCobertura';
        $data = [
            'CodTipoCobertura' => $coverageType,
            'CodRegion' => 99,
        ];

        $clientOptions = array(
            'login'    => "UsrTestServicios",
            'password' => "U$\$vr2\$tS2T",
            'exceptions' => true,
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

        $coberturas = $result->respObtenerCobertura->Coberturas;

        cache(['chilexpress.comunas.' . $coverageType => $coberturas], now()->addSeconds(3600));

        return $coberturas;
    }

    /**
     * Maps comuna information form Chilexpress to Geonames.
     */
    public function persistComunas()
    {
        $geonames = $this->setSlug(
            Geoname::where('country_code', 'CL')->where('feature_code', 'ADM3')->get(),
            'name'
        )->keyBy('geonameid');

        $regiones = ChilexpressGeodata::where('type', 'region')->get();
        $comunasAdmit = collect($this->comunas(1))->keyBy('CodComuna');
        $comunasDeliver = collect($this->comunas(2))->keyBy('CodComuna');

        $comunas = $this->setSlug($comunasAdmit->merge($comunasDeliver), 'GlsComuna');
        $comunas = $this->setCoverageType($comunas, $comunasAdmit, $comunasDeliver)->keyBy('CodComuna');

        $savedGeonames = collect();

        // Iterate over Regions already in Chilexpress DB.
        foreach ($regiones as $region) {
            $comunasGeonames = $geonames->where('admin1_code', $region->geoname->admin1_code);

            $withExactMatch = collect();
            // Iterate over Comunas from WS for the selected Region.
            foreach ($comunas->where('CodRegion', $region->region_cod) as $comuna) {
                // Try an exact match with slugs.
                if ($geoname = $comunasGeonames->firstWhere('slug', $comuna->slug)) {
                    $savedGeonames->prepend($geoname, $geoname->geonameid);
                    $comunasGeonames->forget($geoname->geonameid);
                    $withExactMatch->prepend($comuna, $comuna->CodComuna);
                    $this->createChilexpressGeodata($geoname, $comuna);
                }
            }

            // Iterate over Comunas from WS for the selected Region where no
            // exact match was found.
            foreach ($comunas->diffKeys($withExactMatch)->where('CodRegion', $region->region_cod) as $comuna) {
                // Iterate over Comunas from Geonames for the selected Region.
                foreach ($comunasGeonames as $geoname) {
                    // No partial match, continue.
                    if (strpos($geoname->slug, $comuna->slug)  === false
                        && strpos($comuna->slug, $geoname->slug) === false) {
                        continue;
                    }

                    $savedGeonames->prepend($geoname, $geoname->geonameid);
                    $this->createChilexpressGeodata($geoname, $comuna);
                    Log::warning(
                        'Equivalencia parcial de Geoname con comuna de Chilexpress.',
                        ['comuna' => $comuna, 'geoname' => $geoname]
                    );
                }

                // No match was found :(
                Log::error('Sin equivalencia en Geonames para comuna de Chilexpress.', ['comuna' => $comuna]);
            }
        }

        $notSavedGeonames = $geonames->diffKeys($savedGeonames);
        if ($notSavedGeonames->count() > 0) {
            foreach ($notSavedGeonames as $geoname) {
                Log::error('Sin equivalencia en Chilexpress para comuna de Geonames.', ['geoname' => $geoname]);
            }
        }
    }

    /**
     * Iterate over every comuna and add a slug field to it
     * created from the property from $nameProperty.
     */
    protected function setSlug($comunas, $nameField)
    {
        return $comunas->each(function ($comuna) use ($nameField) {
            $slug = str_replace('-', '', str_slug(data_get($comuna, $nameField)));
            data_Set($comuna, 'slug', $slug);
        });
    }

    protected function setCoverageType($comunas, $inAdmit, $inDeliver)
    {
        $inBoth = $inAdmit->intersectByKeys($inDeliver);

        return $comunas->each(function ($comuna) use ($inAdmit, $inDeliver, $inBoth) {
            switch (true) {
                case $inBoth->has($comuna->CodComuna):
                    $comuna->CoverageType = 3;
                    break;

                case $inDeliver->has($comuna->CodComuna):
                    $comuna->CoverageType = 2;
                    break;

                case $inAdmit->has($comuna->CodComuna):
                    $comuna->CoverageType = 1;
                    break;
            }
        });
    }


    protected function createChilexpressGeodata($geoname, $comuna)
    {
        // Load existing Geodata if one is found.
        $chilexpressGeodata = ChilexpressGeodata::firstOrNew(['geonameid' => $geoname->geonameid]);

        // Partial matches mean that we might match the same geoname to multiple comunas.
        if ($chilexpressGeodata && $chilexpressGeodata->comuna_cod !== $comuna->CodComuna) {
            Log::error(
                'Duplicado en Geonames para comuna de Chilexpress.',
                ['comuna' => $comuna, - 'chilexpressGeodata' => $chilexpressGeodata, 'geoname' => $geoname]
            );
        }

        $chilexpressGeodata->name = $comuna->GlsComuna;
        $chilexpressGeodata->type = 'comuna';
        $chilexpressGeodata->region_cod = $comuna->CodRegion;
        $chilexpressGeodata->comuna_cod = $comuna->CodComuna;
        $chilexpressGeodata->comuna_cod_ine = $comuna->CodComunaIne;
        $chilexpressGeodata->coverage_type = $comuna->CoverageType;
        $chilexpressGeodata->save();
    }
}
