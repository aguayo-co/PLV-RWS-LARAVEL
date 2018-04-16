<?php

namespace App\Traits;

trait SaveLater
{
    /**
     * Store files temporarily while creating a product.
     */
    protected $save_later_data = [];

    protected function saveLater($attribute, $data)
    {
        if (!$this->id) {
            $this->save_later_data[$attribute] = $data;
            return true;
        }
        return false;
    }

    protected static function registerSavesNow()
    {
        self::created(function ($model) {
            foreach ($model->save_later_data as $key => $value) {
                $model->$key = $value;
                array_forget($model->save_later_data, $key);
            }
        });
    }

    public static function bootSaveLater()
    {
        self::registerSavesNow();
    }
}
