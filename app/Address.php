<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'address', 'region', 'city', 'zone',
    ];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }


    /**
     * Custom binding to load a address.
     *
     * Check the user form the URL is the owner of the address.
     */
    public function resolveRouteBinding($value)
    {
        $user = request()->user;
        if (!$user) {
            return;
        }

        $address = parent::resolveRouteBinding($value);
        if (!$address) {
            return;
        }

        if ($user->id !== $address->user_id) {
            return;
        }

        return $address;
    }
}
