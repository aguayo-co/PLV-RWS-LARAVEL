<?php

use Faker\Generator as Faker;
use App\Size;

$factory->define(Size::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
    ];
});

$factory->state(Size::class, 'child', function () {
    return [
        'parent_id' => Size::whereNull('parent_id')->get()->random()->id,
    ];
});
