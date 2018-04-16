<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasSingleFile;

class Banner extends Model
{
    use HasSingleFile;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'title', 'subtitle', 'button_text', 'url', 'image',
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

    protected function setImageAttribute(?UploadedFile $image)
    {
        $this->setFile('image', $image);
    }

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = $name;
        $this->attributes['slug'] = str_slug($name);
    }
}
