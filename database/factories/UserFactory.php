<?php

use Faker\Generator as Faker;
use App\Group;
use Illuminate\Http\UploadedFile;
use App\ShippingMethod;

$factory->define(App\User::class, function (Faker $faker) {
    $groups = Group::setEagerLoads([])->get(['id'])->pluck('id')->all();
    $groupsCount = $faker->numberBetween(0, count($groups));

    return [
        'email' => $faker->unique()->safeEmail,
        'password' => 'secret',
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'group_ids' => $faker->randomElements($groups, $groupsCount),
    ];
});

$factory->state(App\User::class, 'profile', function ($faker) {
    $shippingMethods = ShippingMethod::setEagerLoads([])->get(['id'])->pluck('id')->all();
    $shippingMethodsCount = $faker->numberBetween(1, count($shippingMethods));

    return [
        'phone' => $faker->phoneNumber,
        'about' => $faker->paragraph,
        'cover' => UploadedFile::fake()->image('cover'),
        'picture' => UploadedFile::fake()->image('picture'),
        'shipping_method_ids' => $faker->randomElements($shippingMethods, $shippingMethodsCount),
    ];
});

$factory->state(App\User::class, 'profile_with_real_images', function ($faker) {
    $shippingMethods = ShippingMethod::setEagerLoads([])->get(['id'])->pluck('id')->all();
    $shippingMethodsCount = $faker->numberBetween(1, count($shippingMethods));

    return [
        'phone' => $faker->phoneNumber,
        'about' => $faker->paragraph,
        'cover' => new UploadedFile($faker->image, 'cover'),
        'picture' => new UploadedFile($faker->image, 'picture'),
        'shipping_method_ids' => $faker->randomElements($shippingMethods, $shippingMethodsCount),
    ];
});
