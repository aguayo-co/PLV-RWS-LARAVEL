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
            'favoriteAddress.geoname.admin1',
            'favoriteAddress.geoname.admin2',
            'followers:id',
            'following:id',
            'groups:id',
            'productsPublished:id,user_id,status',
            'shippingMethods:id',
        ]);
        $collection->makeHidden([
            'favorites',
            'favoriteAddress',
            'followers',
            'following',
            'groups',
            'productsPublished',
            'ratings_positive_count',
            'rating_archives_positive_count',
            'ratings_buyer_positive_count',
            'ratings_neutral_count',
            'rating_archives_neutral_count',
            'ratings_buyer_neutral_count',
            'ratings_negative_count',
            'rating_archives_negative_count',
            'ratings_buyer_negative_count',
            'shippingMethods',
        ]);
        $collection->each(function ($user) {
            $user->append([
                'favorites_ids',
                'location',
                'followers_ids',
                'following_ids',
                'group_ids',
                'published_products_count',
                'shipping_method_ids',
                'ratings_negative_total_count',
                'ratings_neutral_total_count',
                'ratings_positive_total_count',
            ]);
        });
    }
}
