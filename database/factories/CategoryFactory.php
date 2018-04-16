<?php

use Faker\Generator as Faker;
use App\Category;

$factory->define(Category::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
    ];
});

$factory->state(Category::class, 'subcategory', function () {
    return [
        'parent_id' => Category::whereNull('parent_id')->get()->random()->id,
    ];
});
