<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Database\Eloquent\Collection;

trait UserVisibility
{
    protected function setVisibility(Collection $collection)
    {
        $loggedUser = auth()->user();
        switch (true) {
            // Show email for admins and for same user.
            case $collection->count() === 1 && $collection->first()->is($loggedUser):
            case $loggedUser && $loggedUser->hasRole('admin'):
                $collection->makeVisible('email');
        }
        $collection->load(['followers:id', 'following:id', 'shippingMethods', 'favorites:id']);
        $collection->each(function ($user) {
            $user->append([
                'followers_ids',
                'following_ids',
                'following_count',
                'followers_count',
                'shipping_method_ids',
                'favorites_ids',
                'group_ids',
                'credits',
                'purchased_products_count',
                'published_products_count',
                'sold_products_count',
            ]);
        });
    }
}
