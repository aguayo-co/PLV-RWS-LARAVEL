<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CloudFile extends Model
{
    protected $fillable = ['attribute', 'urls'];

    protected $casts = [
        'urls' => 'array',
    ];
}
