<?php

namespace App\Http\Traits;

use Illuminate\Support\Str;

trait MessagesFilter
{
    protected function bodyFilterRule()
    {
        return function ($attribute, $value, $fail) {
            $message = __('¡Ups! Tu mensaje no será enviado por el uso de palabras inapropiadas en Prilov. Ayúdanos a mantener segura nuestra comunidad de Prilovers.');

            $value = Str::ascii($value);

            $words = [
                'Efectivo',
                'whatsapp',
                'cash',
                'facebook',
                'wsp',
                'fb',
                'face',
                'cel',
                'whatsap',
                'ferio',
                'feriaferio'
            ];
            // Word filter!
            if (preg_match('/(?<=[^a-z]|^)(' . implode('|', $words) . ')(?=[^a-z]|$)/i', $value)) {
                return $fail($message);
            }

            // Remove up to two characters between numbers:
            $numberMatch = preg_replace('/(?<=[0-9])([^0-9]{0,2})(?=[0-9])/', '', $value);
            // If more than 5 digits are next to each other, fail!
            if (preg_match('/[0-9]{7,}/', $numberMatch)) {
                return $fail($message);
            }
        };
    }
}
