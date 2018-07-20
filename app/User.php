<?php

namespace App;

use App\Traits\DateSerializeFormat;
use App\Traits\HasSingleFile;
use Cmgmyr\Messenger\Traits\Messagable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Messagable;
    use Notifiable;
    use HasRoles;
    use HasApiTokens;
    use HasSingleFile;
    use DateSerializeFormat;

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
    ];

    protected $appends = [
        'cover',
        'picture',
    ];

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
        return $this->belongsToMany('App\Product', 'favorites');
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

    protected function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
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

    protected function getPublishedProductsCountAttribute()
    {
        return $this->products->where('status', '>=', Product::STATUS_APPROVED)
            ->where('status', '<=', Product::STATUS_AVAILABLE)->count();
    }

    protected function getSoldProductsCountAttribute()
    {
        return $this->products->where('status', '>=', Product::STATUS_PAYMENT)
            ->where('status', '<=', Product::STATUS_SOLD_RETURNED)->count();
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

    protected function getRatingsNegativeCountAttribute()
    {
        $new = $this->ratings->whereStrict('status', Rating::STATUS_PUBLISHED)
            ->whereStrict('buyer_rating', -1)->count();
        $archive = $this->ratingArchives->whereStrict('buyer_rating', -1)->count();
        return $new + $archive;
    }

    protected function getRatingsNeutralCountAttribute()
    {
        $new = $this->ratings->whereStrict('status', Rating::STATUS_PUBLISHED)
            ->whereStrict('buyer_rating', 0)->count();
        $archive = $this->ratingArchives->whereStrict('buyer_rating', 0)->count();
        return $new + $archive;
    }

    protected function getRatingsPositiveCountAttribute()
    {
        $new = $this->ratings->whereStrict('status', Rating::STATUS_PUBLISHED)
            ->whereStrict('buyer_rating', 1)->count();
        $archive = $this->ratingArchives->whereStrict('buyer_rating', 1)->count();
        return $new + $archive;
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
        return $this->belongsToMany(User::class, 'follower_followee', 'followee_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follower_followee', 'follower_id', 'followee_id');
    }
    #                                 #
    # End Following-Follower methods. #
    #                                 #

    /**
     * Order users by their group_ids relation.
     * Always shows users with no groups at the end.
     *
     * Can change the direction based on the lowest id of all
     * the groups a user belongs to.
     */
    public function scopeOrderedByGroup($query, $direction = 'asc')
    {
        $subQuery = DB::table('group_user')
            ->select('user_id')
            ->selectRaw('MIN(group_id) as group_id')
            ->selectRaw('MIN(group_id) IS NOT NULL as has_group')
            ->groupBy('user_id');
        return $query
            ->leftJoinSub($subQuery, 'group_user', 'group_user.user_id', '=', 'users.id')
            ->orderBy('group_user.has_group', 'desc')
            ->orderBy('group_user.group_id', $direction);
    }

    /**
     * Order users by their group_ids relation.
     * Always shows users with no groups at the end.
     *
     * Can change the direction based on the lowest id of all
     * the groups a user belongs to.
     */
    public function scopeOrderedByLatestProduct($query, $direction = 'asc')
    {
        $subQuery = DB::table('products')
            ->select('user_id')
            ->selectRaw('MAX(created_at) as created_at')
            ->groupBy('user_id');
        return $query
            ->leftJoinSub($subQuery, 'products', 'products.user_id', '=', 'users.id')
            ->orderBy('products.created_at', $direction);
    }

    /**
     * Calculate number of products purchased by the user.
     */
    public function scopeWithPurchasedProductsCount($query)
    {
        if (!$query->getQuery()->columns) {
            $query->addSelect('users.*');
        }

        $subQuery = DB::table('products')
            ->selectRaw('orders.user_id as user_id, COUNT(*) as purchased_products_count')
            ->rightJoin('product_sale', 'products.id', '=', 'product_sale.product_id')
            ->rightJoin('sales', 'product_sale.sale_id', '=', 'sales.id')
            ->rightJoin('orders', 'sales.order_id', '=', 'orders.id')
            ->where('products.status', '>', Product::STATUS_PAYMENT)
            ->whereBetween('sales.status', [Sale::STATUS_PAYED, Sale::STATUS_COMPLETED_PARTIAL])
            ->groupBy(['orders.user_id']);
        return $query->leftJoinSub(
            $subQuery,
            'ppc_sub',
            'ppc_sub.user_id',
            '=',
            'users.id'
        )
        ->addSelect('purchased_products_count');
    }

    /**
     * Calculate available credits, including the ones being used
     * on the current shopping cart.
     */
    public function scopeWithCredits($query)
    {
        if (!$query->getQuery()->columns) {
            $query->addSelect('users.*');
        }

        return $query->leftJoin('credits_transactions', 'credits_transactions.user_id', '=', 'users.id')
            ->leftJoin('orders as tr_orders', 'tr_orders.id', '=', 'credits_transactions.order_id')
            ->where(function ($query) {
                $query->whereNull('credits_transactions.transfer_status')
                    ->orWhere('credits_transactions.transfer_status', '!=', CreditsTransaction::STATUS_REJECTED);
            })
            ->where(function ($query) {
                $query->whereNull('credits_transactions.order_id')
                    ->orWhere('tr_orders.status', '!=', Order::STATUS_SHOPPING_CART);
            })
            ->selectRaw('CAST(SUM(credits_transactions.amount) AS SIGNED) credits')
            ->selectRaw('CAST(SUM(credits_transactions.commission) AS SIGNED) commissions')
            ->groupBy(['users.id']);
    }
}
