<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'parent_id',
    ];

    public function products()
    {
        return $this->HasMany('App\Product');
    }

    public function parent()
    {
        return $this->belongsTo('App\Size');
    }

    public function children()
    {
        return $this->hasMany('App\Size', 'parent_id');
    }
}
