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
                $collection->makeVisible(['email', 'bank_account']);
        }

        $collection->load([
            'followers:id',
            'following:id',
            'shippingMethods',
            'favorites:id',
            'groups',
            'products:id,status',
        ]);
        $collection->makeHidden([
            'followers',
            'following',
            'favorites',
            'groups',
            'products',
        ]);
        $collection->each(function ($user) {
            $user->append([
                'followers_ids',
                'following_ids',
                'following_count',
                'followers_count',
                'shipping_method_ids',
                'favorites_ids',
                'group_ids',
                'published_products_count',
                'sold_products_count',
            ]);
        });
    }
}
