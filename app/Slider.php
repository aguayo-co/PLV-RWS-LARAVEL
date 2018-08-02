<?php

namespace App;

use App\Traits\DateSerializeFormat;
use App\Traits\HasSingleFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Slider extends Model
{
    use HasSingleFile;
    use DateSerializeFormat;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'main_text',
        'small_text',
        'button_text',
        'url',
        'image',
        'image_mobile',
        'orientation',
        'font_color',
        'priority'
    ];

    protected $hidden = ['cloudFiles'];
    protected $with = ['cloudFiles'];
    protected $appends = ['image', 'image_mobile'];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected function getImageAttribute()
    {
        return $this->getFileUrl('image');
    }

    protected function setImageAttribute($image)
    {
        $this->setFile('image', $image);
    }


    protected function getImageMobileAttribute()
    {
        return $this->getFileUrl('image_mobile');
    }

    protected function setImageMobileAttribute($image)
    {
        $this->setFile('image_mobile', $image);
    }

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = $name;
        $this->attributes['slug'] = str_slug($name);
    }
}
