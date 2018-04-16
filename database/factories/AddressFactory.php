<?php

use Faker\Generator as Faker;
use App\User;

$factory->define(App\Address::class, function (Faker $faker) {
    return [
        'user_id'=> User::all()->random()->id,
        'address'=> $faker->streetAddress,
        'region'=> $faker->city,
        'city'=> $faker->city,
        'zone'=> $faker->cityPrefix,
    ];
});
