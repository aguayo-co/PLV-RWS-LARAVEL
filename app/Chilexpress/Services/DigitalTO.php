<?php

namespace App\Chilexpress\Services;

trait DigitalTO
{
    /**
     * Generate label for given Shipping details.
     *
     * @param \App\User $seller.
     * @param \App\User $buyer.
     * @param \App\Address $origen.
     * @param \App\Address $destino.
     * @param int $weight Kg
     * @param int $height cm
     * @param int $width cm
     * @param int $length cm
     *
     * @return mixed null or image blob
     */
    public function order($ref, $seller, $buyer, $origen, $destino, $weight, $height, $width, $length)
    {
        $route = "IntegracionAsistidaOp";

        $method = 'reqGenerarIntegracionAsistida';
        $data = [
            'codigoProducto' => '3',
            'codigoServicio' => '3',
            'comunaOrigen' => $origen->chilexpressGeodata->name,
            'numeroTCC' => '22106942',
            'referenciaEnvio' => 'PRILOV - ' . $ref,
            'referenciaEnvio2' => null,
            'eoc' => '0',
            'Remitente' => [
                'nombre' => $seller->full_name,
                'email' => $seller->email,
                'celular' => $seller->phone,
            ],
            'Destinatario' => [
                'nombre' => $buyer->full_name,
                'email' => $buyer->email,
                'celular' => $buyer->phone,
            ],
            'Direccion' => [
                'comuna' => $destino->chilexpressGeodata->name,
                'calle' => $destino->street,
                'numero' => $destino->number,
                'complemento' => $destino->additional,
            ],
            'DireccionDevolucion' => [
                'comuna' => $origen->chilexpressGeodata->name,
                'calle' => $origen->street,
                'numero' => $origen->number,
                'complemento' => $origen->additional,
            ],
            'Pieza' => [
                'peso' => $weight,
                'alto' => $height,
                'ancho' => $width,
                'largo' => $length,
            ],
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

        $client = new \SoapClient(dirname(__DIR__) . "/wsdl/GenerarOTDigitalIndividualC2C.wsdl", $clientOptions);
        $result = $client->__soapCall($route, [ $route => [ $method => $data ] ]);

        if ($result->respGenerarIntegracionAsistida->EstadoOperacion->codigoEstado !== 0) {
            return -1;
        }

        return $result->respGenerarIntegracionAsistida->DatosEtiqueta->imagenEtiqueta;
    }
}
