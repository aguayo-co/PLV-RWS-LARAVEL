<?php

use Faker\Generator as Faker;
use Illuminate\Http\UploadedFile;

$factory->define(App\Slider::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
        'main_text' => $faker->sentence(5),
        'small_text' => $faker->optional()->sentence(4),
        'button_text' => $faker->optional()->sentence(2),
        'url' => $faker->url,
        'image' => UploadedFile::fake()->image('image'),
        'orientation' => $faker->randomElement(['left', 'right']),
        'font_color' => $faker->randomElement(['white', 'black']),
        'priority' => $faker->numberBetween(0, 100),
    ];
});

$factory->state(App\Slider::class, 'with_real_image', function ($faker) {
    return [
        'image' => new UploadedFile($faker->image, 'image'),
    ];
});
