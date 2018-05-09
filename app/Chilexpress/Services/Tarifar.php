<?php

namespace App\Chilexpress\Services;

use App\ChilexpressGeodata;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait Tarifar
{
    /**
     * Calculate shipping cost.
     *
     * @param \App\Address $origen.
     * @param \App\Address $destino.
     * @param int $weight Kg
     * @param int $height cm
     * @param int $width cm
     * @param int $length cm
     *
     * @return mixed null or int
     */
    public function tarifar($origen, $destino, $weight, $height, $width, $length)
    {
        if (!$origen->chilexpressGeodata) {
            Log::error('Tarifar: Origen address has no ChilexpressGeodata.', ['address' => $origen]);
            return;
        }

        if (!$destino->chilexpressGeodata) {
            Log::error('Tarifar: Destino address has no ChilexpressGeodata.', ['address' => $destino]);
            return;
        }

        $route = "TarificarCourier";

        $method = 'reqValorizarCourier';
        $data = [
            'CodCoberturaOrigen' => $origen->chilexpressGeodata->comuna_cod,
            'CodCoberturaDestino' => $destino->chilexpressGeodata->comuna_cod,
            'PesoPza' => $weight,
            'DimAltoPza' => $height,
            'DimAnchoPza' => $width,
            'DimLargoPza' => $length,
        ];

        Log::warning('Data', ['data' => $data]);

        $cacheKey = implode('.', $data);

        if ($cost = Cache::get($cacheKey)) {
            return $cost;
        }

        $clientOptions = array(
            'login'    => "UsrTestServicios",
            'password' => "U$\$vr2\$tS2T",
            'exceptions' => true,
        );

        switch (App::environment()) {
            case 'production':
                $client = new \SoapClient('http://ws.ssichilexpress.cl/TarificarCourier?wsdl', $clientOptions);
                break;

            default:
                $client = new \SoapClient('http://qaws.ssichilexpress.cl/TarificarCourier?wsdl', $clientOptions);
                $client->__setLocation('http://qaws.ssichilexpress.cl/TarificarCourier');
        }

        $headerBody = array(
            'transaccion' => array(
                'fechaHora'            => date('Y-m-d\TH:i:s.Z\Z', time()),
                'idTransaccionNegocio' => '0',
                'sistema'              => 'ED',
            )
        );
        $header = new \SoapHeader("http://www.chilexpress.cl/TarificaCourier/", 'headerRequest', $headerBody);
        $client->__setSoapHeaders($header);

        $result = $client->__soapCall($route, [ $route => [ $method => $data ] ]);

        if ($result->respValorizarCourier->CodEstado !== 0) {
            return;
        }

        $servicio = collect($result->respValorizarCourier->Servicios)
            ->firstWhere('CodServicio', env('CHILEXPRESS_CODSERVICIO'));
        if (!$servicio) {
            $servicio = collect($result->respValorizarCourier->Servicios)->first();
        }
        $valor = $servicio->ValorServicio;

        Cache::put($cacheKey, $valor, 1440);
        return $valor;
    }
}
