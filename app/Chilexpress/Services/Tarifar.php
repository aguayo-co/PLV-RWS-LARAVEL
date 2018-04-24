<?php

namespace App\Chilexpress\Services;

use App\Chilexpress\Address;
use App\ChilexpressGeodata;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Clase para calculo de tarifa de chilexpress
 */
trait Tarifar
{
    /**
     * Retorna el valor del shipping de Chilexpress
     * @param string $origen Codigo de la ciudad de origen
     * @param string $destino Codigo de la ciudad destino
     * @param int $peso en kilo
     * @param int $alto en cm
     * @param int $ancho en cm
     * @param int $largo en cm
     * @return mixed null o int con valor
     */
    public function tarifar($origenAddress, $destinoAddress, $peso, $alto, $ancho, $largo)
    {

        try {
            $origenAddress = new Address($origenAddress['address'], $origenAddress['region'], $origenAddress['zone']);
        } catch (ModelNotFoundException $e) {
            return null;
        }

        try {
            $destinoAddress = new Address($destinoAddress['address'], $destinoAddress['region'], $destinoAddress['zone']);
        } catch (ModelNotFoundException $e) {
            return null;
        }

        $route = "TarificarCourier";

        $method = 'reqValorizarCourier';
        $data = [
            'CodCoberturaOrigen' => $origenAddress->comuna->comuna_cod,
            'CodCoberturaDestino' => $destinoAddress->comuna->comuna_cod,
            'PesoPza' => $peso,
            'DimAltoPza' => $alto,
            'DimAnchoPza' => $ancho,
            'DimLargoPza' => $largo
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

        $valor = null;

        return $result->respValorizarCourier;
        if ($result->respValorizarCourier->CodEstado == 0) {
            $servicio = collect($result->respValorizarCourier->Servicios)->firstWhere('CodServicio', 3);
            if (!$servicio) {
                $servicio = collect($result->respValorizarCourier->Servicios)->first();
            }
            $valor = $servicio->ValorServicio;
        }

        return $valor;
    }
}
