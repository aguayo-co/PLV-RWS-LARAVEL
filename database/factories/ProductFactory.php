<?php

use App\Brand;
use App\Campaign;
use App\Category;
use App\Color;
use App\Condition;
use App\Product;
use App\Size;
use App\User;
use Faker\Generator as Faker;
use Illuminate\Http\UploadedFile;

$factory->define(Product::class, function (Faker $faker) {
    $colors = Color::setEagerLoads([])->get(['id'])->pluck('id')->all();
    $colors_count = $faker->numberBetween(1, 2);

    $campaigns = Campaign::setEagerLoads([])->get(['id'])->pluck('id')->all();
    $campaigns_count = $faker->numberBetween(0, count($campaigns));

    return [
        'title' => $faker->sentence,
        'description' => $faker->paragraph,
        'dimensions' => $faker->sentence,
        'original_price' => $faker->numberBetween(0, 999999),
        'price' => $faker->numberBetween(0, 499999),
        'commission' => $faker->numberBetween(0, 100),
        'user_id' => User::all()->random()->id,
        'brand_id' => Brand::all()->random()->id,
        'category_id' => Category::whereNotNull('parent_id')->get()->random()->id,
        'size_id' => Size::whereNotNull('parent_id')->get()->random()->id,
        'condition_id' => Condition::all()->random()->id,
        'status' => $faker->randomElement([
            Product::STATUS_APPROVED,
            Product::STATUS_AVAILABLE,
            Product::STATUS_UNPUBLISHED,
            Product::STATUS_UNAVAILABLE
        ]),
        'images' => [UploadedFile::fake()->image('image_1.jpg'), UploadedFile::fake()->image('image_2.jpg'),],
        'color_ids' => $faker->randomElements($colors, $colors_count),
        'campaign_ids' => $faker->randomElements($campaigns, $campaigns_count),
    ];
});

$factory->state(App\Product::class, 'with_real_images', function ($faker) {
    return [
        'images' => [new UploadedFile($faker->image, 'image'), new UploadedFile($faker->image, 'image'),],
    ];
});
