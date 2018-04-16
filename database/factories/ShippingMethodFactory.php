<?php

use Faker\Generator as Faker;

$factory->define(App\ShippingMethod::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
        'description_seller' => $faker->paragraph(),
        'description_buyer' => $faker->paragraph(),
    ];
});
