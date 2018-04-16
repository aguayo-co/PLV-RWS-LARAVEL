<?php

use Faker\Generator as Faker;

$factory->define(App\Condition::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
    ];
});
