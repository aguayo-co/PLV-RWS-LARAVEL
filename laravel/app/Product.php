<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    public const IMAGES_BASE_PATH = 'public/products/images/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'dimensions',
        'original_price',
        'price',
        'commission',
        'brand_id',
        'category_id',
        'condition_id',
        'status_id',
    ];

    protected $with = ['brand', 'colors', 'category.parent', 'condition', 'status'];

    protected $appends = ['images'];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    protected function getImagePathAttribute()
    {
        return $this::IMAGES_BASE_PATH . $this->id . '/';
    }

    protected function getImagesAttribute()
    {
        $images = [];
        foreach (Storage::files($this->image_path) as $image) {
            $images[] = asset($image);
        }
        return $images;
    }

    protected function setImagesAttribute(array $images)
    {
        foreach ($images as $image) {
            $image->storeAs($this->image_path, uniqid());
        }
    }

    protected function setDeleteImagesAttribute(array $images)
    {
        foreach ($images as $image) {
            if ($image && Storage::exists($this->image_path . $image)) {
                Storage::delete($this->image_path . $image);
            }
        }
    }

    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function colors()
    {
        return $this->belongsToMany('App\Color');
    }

    public function condition()
    {
        return $this->belongsTo('App\Condition');
    }

    public function status()
    {
        return $this->belongsTo('App\Status');
    }
}
