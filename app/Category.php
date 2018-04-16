<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'parent_id',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function products()
    {
        return $this->HasMany('App\Product');
    }

    public function parent()
    {
        return $this->belongsTo('App\Category');
    }

    public function children()
    {
        return $this->hasMany('App\Category', 'parent_id');
    }

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = $name;
        $this->attributes['slug'] = str_slug($name);
    }

    /**
     * Custom binding to load a category or a subcategory.
     *
     * Checks the request to determine if a subcategory is being loaded.
     */
    public function resolveRouteBinding($value)
    {
        $subcategorySlug = request()->subcategory;
        if ($subcategorySlug === $value) {
            return $this->where($this->getRouteKeyName(), $value)->
                where('parent_id', request()->category->id)->first();
        }
        return parent::resolveRouteBinding($value);
    }
}
