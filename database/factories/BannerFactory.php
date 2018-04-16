<?php

use Faker\Generator as Faker;
use Illuminate\Http\UploadedFile;

$factory->define(App\Banner::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
        'title' => $faker->sentence,
        'subtitle' => $faker->sentence(5),
        'button_text' => $faker->sentence(2),
        'url' => $faker->url,
        'image' => UploadedFile::fake()->image('image'),
    ];
});

$factory->state(App\Banner::class, 'with_real_image', function ($faker) {
    return [
        'image' => new UploadedFile($faker->image, 'image'),
    ];
});
