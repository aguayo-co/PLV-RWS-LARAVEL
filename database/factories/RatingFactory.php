<?php

use Faker\Generator as Faker;
use App\Sale;

$factory->define(App\Rating::class, function (Faker $faker) {
    $seller_rating = $faker->optional()->numberBetween(-1, 1);
    $buyer_rating = $faker->optional()->numberBetween(-1, 1);
    $ids = Sale::select('id')->setEagerLoads([])->get()->pluck('id')->all();
    return [
        'sale_id' => $faker->unique()->randomElement($ids),
        'seller_rating' => $seller_rating,
        'seller_comment' => !is_null($seller_rating) ? $faker->sentence($faker->numberBetween(1, 25)) : null,
        'buyer_rating' => $buyer_rating,
        'buyer_comment' => !is_null($buyer_rating) ? $faker->sentence($faker->numberBetween(1, 25)) : null,
        'status' => $faker->randomElement(Rating::getStatuses()),
    ];
});
