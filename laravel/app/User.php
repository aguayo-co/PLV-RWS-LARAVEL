<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use App\Traits\SaveLater;
use App\Traits\HasSingleFile;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    use HasApiTokens;
    use HasSingleFile;
    // use SaveLater; # HasSingleFile uses it already.

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'about',
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
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'email',
        'password',
        # By default, hide everything that is self referenced.
        # If not, this might lead to recursion.
        # Important to ->load(['followers:id', 'following:id']) on collections
        # if need to show its _ids or _count attributes.
        'following',
        'followers',
        'following_ids',
        'followers_ids',
        'following_count',
        'followers_count',
    ];

    protected $appends = [
        'cover',
        'picture',
        'shipping_method_ids',
        'favorites_ids',
        'group_ids',
        # These should be hidden to avoid circular references.
        'following_ids',
        'followers_ids',
        'following_count',
        'followers_count',
    ];
    protected $with = ['roles', 'groups', 'shippingMethods', 'favorites:id'];

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
        $role = Role::where(['name' => 'seller'])->first();

        if (!$role) {
            return;
        }

        switch (false) {
            case $this->about:
            case $this->shipping_method_ids->isNotEmpty():
            case $this->cover:
            case $this->phone:
            case $this->picture:
                $this->removeRole($role);
                $this->load('roles');
                return;
            default:
                $this->ensureRole($role);
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

    /**
     * Get the addresses for the user.
     */
    public function addresses()
    {
        return $this->hasMany('App\Address');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Group');
    }

    public function shippingMethods()
    {
        return $this->belongsToMany('App\ShippingMethod');
    }

    public function favorites()
    {
        return $this->belongsToMany('App\Product', 'favorites');
    }

    protected function setGroupIdsAttribute(array $groupIds)
    {
        if ($this->saveLater('group_ids', $groupIds)) {
            return;
        }
        $this->groups()->sync($groupIds);
        $this->load('groups');
        $this->touch();
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
        $this->touch();
    }

    protected function getShippingMethodIdsAttribute()
    {
        return $this->shippingMethods->pluck('id');
    }

    protected function setFavoritesIdsAttribute(array $favoritesIds)
    {
        if ($this->saveLater('favorites_ids', $favoritesIds)) {
            return;
        }
        $this->favorites()->sync($favoritesIds);
        $this->load('favorites');
        $this->touch();
    }

    protected function getFavoritesIdsAttribute()
    {
        return $this->favorites->pluck('id');
    }

    protected function getCoverAttribute()
    {
        return $this->getFileUrl('cover');
    }

    protected function getPictureAttribute()
    {
        return $this->getFileUrl('picture');
    }

    protected function setCoverAttribute(?UploadedFile $cover)
    {
        $this->setFile('cover', $cover);
    }

    protected function setPictureAttribute(?UploadedFile $picture)
    {
        $this->setFile('picture', $picture);
    }

    protected function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    #                                   #
    # Begin CreditsTransaction methods. #
    #                                   #
    public function creditsTransactions()
    {
        return $this->hasMany('App\CreditsTransaction');
    }

    protected function getCreditsAttribute()
    {
        return $this->CreditsTransactions->sum('amount');
    }
    #                                 #
    # End CreditsTransaction methods. #
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
        return $this->followers->pluck('id')->all();
    }

    protected function getFollowingIdsAttribute()
    {
        return $this->following->pluck('id')->all();
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
}
