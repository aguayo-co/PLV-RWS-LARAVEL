<?php

namespace App\Traits;

use DateTime;
use DateTimeInterface;

trait DateSerializeFormat
{
    /**
     * Pass date untouched so that global serialization setting is used.
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date;
    }
}
