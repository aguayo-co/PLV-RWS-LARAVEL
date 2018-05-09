<?php

namespace App\Chilexpress\Services;

use App\ChilexpressGeodata;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        $cacheKey = implode('.', $data);

        if ($cost = Cache::get($cacheKey)) {
            return $cost;
        }

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

        $client = new \SoapClient(dirname(__DIR__) . "/wsdl/WSDL_Tarificacion_QA.wsdl", $clientOptions);
        $headerBody = array(
            'transaccion' => array(
                'fechaHora'            => date('Y-m-d\TH:i:s.Z\Z', time()),
                'idTransaccionNegocio' => '0',
                'sistema'              => 'TEST',
                'usuario'              => 'TEST'
            )
        );

        $header = new \SoapHeader("http://www.chilexpress.cl/TarificaCourier/", 'headerRequest', $headerBody);
        $client->__setSoapHeaders($header);

        $result = $client->__soapCall($route, [ $route => [ $method => $data ] ]);

        if ($result->respValorizarCourier->CodEstado !== 0) {
            return;
        }

        $servicio = collect($result->respValorizarCourier->Servicios)->firstWhere('CodServicio', 3);
        if (!$servicio) {
            $servicio = collect($result->respValorizarCourier->Servicios)->first();
        }
        $valor = $servicio->ValorServicio;

        Cache::put($cacheKey, $valor, 1440);
        return $valor;
    }
}
