<?php

namespace App\Chilexpress;

use App\Chilexpress\Services\Comunas;
use App\Chilexpress\Services\DigitalTO;
use App\Chilexpress\Services\Regiones;
use App\Chilexpress\Services\Tarifar;

/**
 * Clase para calculo de tarifa de chilexpress
 */
class Chilexpress
{
    use Tarifar;
    use DigitalTO;
    use Regiones;
    use Comunas;
}
