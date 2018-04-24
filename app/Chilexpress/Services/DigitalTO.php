<?php

namespace App\Chilexpress\Services;

/**
 * Genera una orden de transporte y su etiqueta
 */
trait DigitalTO
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
    public function order()
    {
        $route = "IntegracionAsistidaOp";

        $method = 'reqGenerarIntegracionAsistida';
        $data = [
            'codigoProducto' => '3',
            'codigoServicio' => '3',
            'comunaOrigen' => 'RENCA',
            'numeroTCC' => '22106942',
            'referenciaEnvio' => 'ENVÍO DE PRILOV',
            'referenciaEnvio2' => 'Compra1',
            'eoc' => '0',
            'Remitente' => [
                'nombre' => 'Mario Moyano',
                'email' => 'mmoyano@chilexpress.cl',
                'celular' => '84642291',
            ],
            'Destinatario' => [
                'nombre' => 'Juan Saab',
                'email' => 'juan.saab@aguayo.co',
                'celular' => '555 123 45 67',
            ],
            'Direccion' => [
                'comuna' => 'PENALOLEN',
                'calle' => 'Camino de las Camelias',
                'numero' => '7909',
                'complemento' => 'Casa 33',
            ],
            'DireccionDevolucion' => [
                'comuna' => 'PUDAHUEL',
                'calle' => 'Jose Joaquin Perez',
                'numero' => '1376',
                'complemento' => 'Piso 2',
            ],
            'Pieza' => [
                'peso' => '5',
                'alto' => '1',
                'ancho' => '1',
                'largo' => '1',
            ],
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

        $client = new \SoapClient(dirname(__DIR__) . "/wsdl/GenerarOTDigitalIndividualC2C.wsdl", $clientOptions);

        $result = $client->__soapCall($route, [ $route => [ $method => $data ] ]);

        if (is_soap_fault($result)) {
            return null;
        }

        $image = $result->respGenerarIntegracionAsistida->DatosEtiqueta->imagenEtiqueta;
        $fp = fopen('image', 'w');
        fwrite($fp, $image);
        fclose($fp);
    }
}
