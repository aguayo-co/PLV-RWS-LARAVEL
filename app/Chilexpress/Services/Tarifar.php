<?php

namespace App\Chilexpress\Services;

use App\ChilexpressGeodata;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

        if (is_soap_fault($result)) {
            return null;
        }

        if ($result->respValorizarCourier->CodEstado !== 0) {
            return null;
        }

        $servicio = collect($result->respValorizarCourier->Servicios)->firstWhere('CodServicio', 3);
        if (!$servicio) {
            $servicio = collect($result->respValorizarCourier->Servicios)->first();
        }
        return $servicio->ValorServicio;
    }
}
