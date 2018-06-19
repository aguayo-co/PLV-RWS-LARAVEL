<?php

use Faker\Generator as Faker;

$factory->define(App\Group::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
    ];
});

$factory->state(App\Group::class, 'with_discount', function ($faker) {
    return [
        'discount_value' => $faker->numberBetween(1, 100),
    ];
});
