<?php

namespace App\Validators;

class EmptyWithValidator
{
    public function validateEmptyWith($attribute, $value, $params, $validator)
    {
        $data = $validator->getData();
        $otherField = $params[0];

        return $value && array_has($data, $otherField) && $data[$otherField] ? false : true;
    }

    public function replaceEmptyWith($message, $attribute, $value, $params, $validator)
    {
        return str_replace(':other', $validator->getDisplayableAttribute($params[0]), $message);
    }
}
