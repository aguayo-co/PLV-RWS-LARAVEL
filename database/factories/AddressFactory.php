<?php

use App\Geoname;
use App\User;
use Faker\Generator as Faker;

$factory->define(App\Address::class, function (Faker $faker) {
    return [
        'user_id'=> User::all()->random()->id,
        'street'=> $faker->streetAddress,
        'number'=> $faker->numberBetween(1, 100),
        'geonameid'=> Geoname::where('feature_code', 'ADM3')->get()->random()->geonameid,
    ];
});
