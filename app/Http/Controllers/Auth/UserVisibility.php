<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Database\Eloquent\Collection;

trait UserVisibility
{
    protected function setVisibility(Collection $collection)
    {
        $loggedUser = auth()->user();
        switch (true) {
            // Show private info for admins and for same user.
            case $collection->count() === 1 && $collection->first()->is($loggedUser):
            case $loggedUser && $loggedUser->hasRole('admin'):
                $collection->makeVisible(['email', 'bank_account', 'phone']);
                $collection->load(['roles']);
                $collection->each(function ($user) {
                    $user->append(['unread_count']);
                });
        }

        $collection->load([
            'favorites:id',
            'followers:id',
            'following:id',
            'groups:id',
            'products:id,user_id,status',
            'shippingMethods:id',
            'ratings',
            'ratingArchives',
        ]);
        $collection->makeHidden([
            'favorites',
            'followers',
            'following',
            'groups',
            'products',
            'shippingMethods',
            'ratings',
            'ratingArchives',
        ]);
        $collection->each(function ($user) {
            $user->append([
                'favorites_ids',
                'followers_count',
                'followers_ids',
                'following_count',
                'following_ids',
                'group_ids',
                'published_products_count',
                'shipping_method_ids',
                'sold_products_count',
                'ratings_negative_count',
                'ratings_neutral_count',
                'ratings_positive_count',
            ]);
        });
    }
}
