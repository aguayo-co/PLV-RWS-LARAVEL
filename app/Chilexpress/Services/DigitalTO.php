<?php

namespace App\Chilexpress\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

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
    public function order($ref, $codigoServicio, $seller, $buyer, $origen, $destino, $weight, $height, $width, $length)
    {
        if (!$origen->chilexpressGeodata) {
            Log::error('DigitalTO: Origen address has no ChilexpressGeodata.', ['address' => $origen]);
            return -1;
        }

        if (!$destino->chilexpressGeodata) {
            Log::error('DigitalTO: Destino address has no ChilexpressGeodata.', ['address' => $destino]);
            return -1;
        }

        $route = "IntegracionAsistidaOp";

        $method = 'reqGenerarIntegracionAsistida';
        $data = [
            'codigoProducto' => '3',
            'codigoServicio' => $codigoServicio,
            'comunaOrigen' => $origen->chilexpressGeodata->name,
            'numeroTCC' => env('CHILEXPRESS_TCC'),
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
            'login'    => env('CHILEXPRESS_USER'),
            'password' => env('CHILEXPRESS_PASS'),
            'exceptions' => true,
        );

        switch (App::environment()) {
            case 'production':
                $client = new \SoapClient('http://ws.ssichilexpress.cl/OSB/GenerarOTDigitalIndividualC2C?wsdl', $clientOptions);
                break;

            default:
                $client = new \SoapClient('http://qaws.ssichilexpress.cl/OSB/GenerarOTDigitalIndividualC2C?wsdl', $clientOptions);
                $client->__setLocation('http://qaws.ssichilexpress.cl/OSB/GenerarOTDigitalIndividualC2C');
        }

        $result = $client->__soapCall($route, [ $route => [ $method => $data ] ]);

        if ($result->respGenerarIntegracionAsistida->EstadoOperacion->codigoEstado !== 0) {
            Log::warning("SOAP Chilexpress service failed.", [
                'estado' => $result->respGenerarIntegracionAsistida->EstadoOperacion
            ]);
            return -1;
        }

        return $result->respGenerarIntegracionAsistida->DatosEtiqueta;
    }
}
