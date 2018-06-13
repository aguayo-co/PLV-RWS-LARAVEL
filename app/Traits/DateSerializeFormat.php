<?php

namespace App\Traits;

use DateTime;
use DateTimeInterface;

trait DateSerializeFormat
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format(DateTime::ATOM);
    }
}
