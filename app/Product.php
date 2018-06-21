<?php

namespace App;

use App\Traits\DateSerializeFormat;
use App\Traits\HasSingleFile;
use App\Traits\HasStatuses;
use App\Traits\ProductPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Product extends Model
{
    use ProductPrice;
    use HasStatuses;
    use HasSingleFile;
    use DateSerializeFormat;

    const STATUS_UNPUBLISHED = 0;
    const STATUS_REJECTED = 1;
    const STATUS_HIDDEN = 2;
    const STATUS_CHANGED_FOR_APPROVAL = 3;
    const STATUS_APPROVED = 10;
    const STATUS_AVAILABLE = 19;
    const STATUS_UNAVAILABLE = 20;
    const STATUS_ON_VACATION = 29;
    const STATUS_PAYMENT = 30;
    const STATUS_SOLD = 31;
    const STATUS_SOLD_RETURNED = 32;

    protected const IMAGES_BASE_PATH = 'public/product/images/';

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
        'size_id',
        'condition_id',
        'status',
        'images',
        'image_instagram',
        'images_remove',
        'color_ids',
        'campaign_ids',
        'admin_notes',
    ];
    /**
     * The attributes that should be hidden.
     *
     * @var array
     */
    protected $hidden = [
        'admin_notes',
    ];
    protected $appends = ['images', 'image_instagram', 'sale_price'];

    protected function getEditableAttribute()
    {
        return Product::STATUS_APPROVED <= $this->status && $this->status < Product::STATUS_PAYMENT;
    }

    protected function getSaleableAttribute()
    {
        return Product::STATUS_APPROVED <= $this->status && $this->status <= Product::STATUS_AVAILABLE;
    }

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany('App\User', 'favorites');
    }

    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

    public function campaigns()
    {
        return $this->belongsToMany('App\Campaign');
    }

    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function size()
    {
        return $this->belongsTo('App\Size');
    }

    public function colors()
    {
        return $this->belongsToMany('App\Color');
    }

    public function condition()
    {
        return $this->belongsTo('App\Condition');
    }

    public function sales()
    {
        return $this->belongsToMany('App\Sale')->withPivot('sale_return_id', 'price');
    }

    protected function setTitleAttribute($title)
    {
        $this->attributes['title'] = $title;
        $this->attributes['slug'] = str_slug($title);
    }

    protected function getColorIdsAttribute()
    {
        return $this->colors->pluck('id');
    }

    protected function setColorIdsAttribute(array $colorIds)
    {
        if ($this->saveLater('color_ids', $colorIds)) {
            return;
        }
        $this->colors()->sync($colorIds);
        $this->load('colors');
    }

    protected function getCampaignIdsAttribute()
    {
        return $this->campaigns->pluck('id');
    }

    protected function setCampaignIdsAttribute(array $campaignIds)
    {
        if ($this->saveLater('campaign_ids', $campaignIds)) {
            return;
        }
        $this->campaigns()->sync($campaignIds);
        $this->load('campaigns');
    }

    #                                   #
    # Start Images methods.             #
    #                                   #
    protected function getImagePathAttribute()
    {
        $idPath = implode(str_split(str_pad($this->id, 9, 0, STR_PAD_LEFT), 3), '/');
        return $this::IMAGES_BASE_PATH . $idPath . '/';
    }

    protected function getImagesAttribute()
    {
        $imagePath = $this->image_path;
        $images = Cache::get($imagePath);
        if ($images) {
            return $images;
        }

        $images = [];
        foreach (Storage::files($imagePath) as $image) {
            $images[] = asset(Storage::url($image));
        }

        Cache::put($imagePath, $images, 1440);
        return $images;
    }

    protected function setImagesAttribute(array $images)
    {
        if ($this->saveLater('images', $images)) {
            return;
        }

        foreach ($images as $image) {
            # Use Intervention Image to process the image
            $processedImage = Image::make($image)->encode('jpg', 80);
            $processedImage->stream();
            $filename = uniqid() . '.jpg';
            Storage::put($this->image_path . $filename, $processedImage->__toString());
        }
        # Timestamps might not get updated if this was the only attribute that
        # changed in the model. Force timestamp update.
        $this->updateTimestamps();
    }

    protected function setImagesRemoveAttribute(array $images)
    {
        $imagePath = $this->image_path;
        Cache::forget($imagePath);

        foreach ($images as $image) {
            if ($image && Storage::exists($this->image_path . $image)) {
                Storage::delete($this->image_path . $image);
            }
        }
    }

    protected function getImageInstagramAttribute()
    {
        return $this->getFileUrl('image_instagram');
    }

    protected function setImageInstagramAttribute($cover)
    {
        $this->setFile('image_instagram', $cover);
    }
    #                                   #
    # End Images methods.               #
    #                                   #
}
