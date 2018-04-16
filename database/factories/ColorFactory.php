<?php

use Faker\Generator as Faker;

$factory->define(App\Color::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
        'hex_code' => $faker->hexColor(),
    ];
});
