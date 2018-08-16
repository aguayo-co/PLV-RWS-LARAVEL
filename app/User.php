<?php

namespace App;

use App\Traits\DateSerializeFormat;
use App\Traits\HasSingleFile;
use App\Traits\UserScopes;
use Cmgmyr\Messenger\Traits\Messagable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use DateSerializeFormat;
    use HasApiTokens;
    use HasRoles;
    use HasSingleFile;
    use Messagable;
    use Notifiable;
    use SoftDeletes;
    use UserScopes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'about',
        'bank_account',
        'cover',
        'email',
        'first_name',
        'last_name',
        'password',
        'phone',
        'picture',
        'vacation_mode',
        'group_ids',
        'shipping_method_ids',
        'favorite_address_id',
    ];

    /**
     * The attributes that should be hidden.
     *
     * @var array
     */
    protected $hidden = [
        'bank_account',
        'email',
        'phone',
        'password',
        'cloudFiles',
    ];
    protected $with = ['cloudFiles'];
    protected $appends = [
        'cover',
        'picture',
    ];

    protected $dates = ['deleted_at'];

    public static function boot()
    {
        parent::boot();
        self::saved(function ($user) {
            $user->validateSeller();
        });
    }

    /**
     * To be have "seller" role must have values in all the listed fields.
     * With one field empty the role is lost.
     */
    protected function validateSeller()
    {
        switch (false) {
            case $this->about:
            case $this->favorite_address_id:
            case $this->shipping_method_ids->isNotEmpty():
            case $this->phone:
            case $this->picture:
                if ($this->hasRole('seller')) {
                    $this->removeRole('seller');
                    $this->load('roles');
                }
                return;
            default:
                $this->ensureRole('seller');
        }
    }

    /**
     * Ensures that role is set.
     */
    protected function ensureRole($role)
    {
        if ($this->hasRole($role)) {
            return;
        }
        $this->assignRole($role);
        $this->load('roles');
    }

    /**
     * Hash password.
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    public function addresses()
    {
        return $this->hasMany('App\Address');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Group');
    }

    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    public function products()
    {
        return $this->hasMany('App\Product');
    }

    public function shippingMethods()
    {
        return $this->belongsToMany('App\ShippingMethod');
    }

    public function favorites()
    {
        return $this->belongsToMany('App\Product', 'favorites')->setEagerLoads([]);
    }

    protected function setGroupIdsAttribute(?array $groupIds)
    {
        if ($this->saveLater('group_ids', $groupIds)) {
            return;
        }
        $this->groups()->sync($groupIds);
        $this->load('groups');
    }

    protected function getGroupIdsAttribute()
    {
        return $this->groups->pluck('id');
    }

    protected function setShippingMethodIdsAttribute(array $shippingMethodIds)
    {
        if ($this->saveLater('shipping_method_ids', $shippingMethodIds)) {
            return;
        }
        $this->shippingMethods()->sync($shippingMethodIds);
        $this->load('shippingMethods');
    }

    protected function getShippingMethodIdsAttribute()
    {
        return $this->shippingMethods->pluck('id');
    }

    protected function getCoverAttribute()
    {
        return $this->getFileUrl('cover');
    }

    protected function getPictureAttribute()
    {
        return $this->getFileUrl('picture');
    }

    protected function setCoverAttribute($cover)
    {
        $this->setFile('cover', $cover);
    }

    protected function setPictureAttribute($picture)
    {
        $this->setFile('picture', $picture);
    }

    public function setBankAccountAttribute($value)
    {
        $this->attributes['bank_account'] = json_encode($value);
    }

    public function getBankAccountAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getUnreadCountAttribute()
    {
        return $this->newThreadsCount();
    }

    #                                     #
    # Begin Products Information methods. #
    #                                     #
    protected function setFavoritesIdsAttribute(array $favoritesIds)
    {
        if ($this->saveLater('favorites_ids', $favoritesIds)) {
            return;
        }
        $this->favorites()->sync($favoritesIds);
        $this->load('favorites');
    }

    protected function getFavoritesIdsAttribute()
    {
        return $this->favorites->pluck('id');
    }

    public function productsPublished()
    {
        return $this->products()->setEagerLoads([])->where('status', '>=', Product::STATUS_APPROVED)
            ->where('status', '<=', Product::STATUS_AVAILABLE);
    }

    protected function getPublishedProductsCountAttribute()
    {
        return $this->productsPublished->count();
    }

    public function productsSold()
    {
        return $this->products()->setEagerLoads([])->where('status', '>=', Product::STATUS_PAYMENT)
            ->where('status', '<=', Product::STATUS_SOLD_RETURNED);
    }

    protected function getSoldProductsCountAttribute()
    {
        return $this->productsSold->count();
    }
    #                                   #
    # End Products Information methods. #
    #                                   #

    #                                   #
    # Begin CreditsTransaction methods. #
    #                                   #
    public function creditsTransactions()
    {
        return $this->hasMany('App\CreditsTransaction');
    }
    #                                 #
    # End CreditsTransaction methods. #
    #                                 #

    #                                   #
    # Begin Ratings methods.            #
    #                                   #
    public function ratings()
    {
        return $this->hasManyThrough('App\Rating', 'App\Sale');
    }

    public function ratingArchives()
    {
        return $this->hasMany('App\RatingArchive', 'seller_id');
    }

    public function ratingsNegative()
    {
        return $this->ratings()->where('ratings.status', Rating::STATUS_PUBLISHED)
            ->where('buyer_rating', -1);
    }

    public function ratingArchivesNegative()
    {
        return $this->ratingArchives()->where('buyer_rating', -1);
    }

    public function ratingsNeutral()
    {
        return $this->ratings()->where('ratings.status', Rating::STATUS_PUBLISHED)
            ->where('buyer_rating', 0);
    }

    public function ratingArchivesNeutral()
    {
        return $this->ratingArchives()->where('buyer_rating', 0);
    }

    public function ratingsPositive()
    {
        return $this->ratings()->where('ratings.status', Rating::STATUS_PUBLISHED)
            ->where('buyer_rating', 1);
    }

    public function ratingArchivesPositive()
    {
        return $this->ratingArchives()->where('buyer_rating', 1);
    }

    protected function getRatingsNegativeCountAttribute()
    {
        return $this->ratingsNegative->count() + $this->ratingArchivesNegative->count();
    }

    protected function getRatingsNeutralCountAttribute()
    {
        return $this->ratingsNeutral->count() + $this->ratingArchivesNeutral->count();
    }

    protected function getRatingsPositiveCountAttribute()
    {
        return $this->ratingsPositive->count() + $this->ratingArchivesPositive->count();
    }
    #                                 #
    # End Ratings methods.            #
    #                                 #

    #                                   #
    # Begin Following-Follower methods. #
    #                                   #
    protected function getFollowersCountAttribute()
    {
        return $this->followers->count();
    }

    protected function getFollowingCountAttribute()
    {
        return $this->following->count();
    }

    protected function getFollowersIdsAttribute()
    {
        return $this->followers->pluck('id');
    }

    protected function getFollowingIdsAttribute()
    {
        return $this->following->pluck('id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follower_followee', 'followee_id', 'follower_id')->setEagerLoads([]);
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follower_followee', 'follower_id', 'followee_id')->setEagerLoads([]);
    }
    #                                 #
    # End Following-Follower methods. #
    #                                 #
}
