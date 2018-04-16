<?php

namespace App\Traits;

trait HasStatuses
{
    /**
     * Returns an array of the constants from the class that
     * begin with STATUS_ with its values.
     */
    public static function getStatuses()
    {
        $reflection = new \ReflectionClass(__CLASS__);
        return array_filter($reflection->getConstants(), function ($key) {
            return substr($key, 0, 7) === 'STATUS_';
        }, ARRAY_FILTER_USE_KEY);
    }
}
