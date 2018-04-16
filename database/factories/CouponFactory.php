<?php

use Faker\Generator as Faker;

$factory->define(App\Coupon::class, function (Faker $faker) {
    return [
        'description' => $faker->sentence(),
        'code' => $faker->unique()->word(),
        'discount_value' => $faker->numberBetween(1, 10000),
        'discount_type' => '$',
    ];
});

$factory->state(App\Coupon::class, '%', function ($faker) {
    return [
        'discount_value' => $faker->numberBetween(1, 100),
        'discount_type' => '%',
    ];
});

$factory->state(App\Coupon::class, 'random', function ($faker) {
    $validFrom = $faker->optional()->dateTimeBetween('-1 month', '+ 1 month');
    $validToStartDate = $validFrom ? $validFrom->format(DATE_ATOM) : '-1 month';
    return [
        'valid_from' => $validFrom,
        'valid_to' => $faker->optional()->dateTimeBetween($validToStartDate, '+ 2 months'),
        'minimum_price' => $faker->numberBetween(0, 10000),
        'minimum_commission' => $faker->numberBetween(0, 100),
        'first_purchase_only' => $faker->boolean,
        'status' => $faker->randomElement(Coupon::getStatuses()),
    ];
});
