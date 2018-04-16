<?php

use App\Order;
use App\Sale;
use App\User;
use Faker\Generator as Faker;

$factory->define(App\CreditsTransaction::class, function (Faker $faker) {
    $association = $faker->randomElement(['sale_id', 'order_id', null]);
    return [
        'user_id' => User::all()->random()->id,
        'amount' => $faker->numberBetween(-499999, 499999),
        // 'sale_id' => $association === 'sale_id' ? Sale::all()->random()->id : null,
        // 'order_id' => $association === 'order_id' ? Order::all()->random()->id : null,
        'extra' => [],
    ];
});
