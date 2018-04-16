<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasSingleFile;

class Slider extends Model
{
    use HasSingleFile;

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

    protected $appends = ['image'];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected function getImageAttribute()
    {
        return $this->getFileUrl('image');
    }

    protected function setImageAttribute(UploadedFile $image)
    {
        $this->setFile('image', $image);
    }


    protected function getImageMobileAttribute()
    {
        return $this->getFileUrl('image_mobile');
    }

    protected function setImageMobileAttribute(UploadedFile $image)
    {
        $this->setFile('image_mobile', $image);
    }

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = $name;
        $this->attributes['slug'] = str_slug($name);
    }
}
