<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'seller']);

        factory(App\Group::class, 5)->create();
        factory(App\ShippingMethod::class, 2)->create();

        factory(App\User::class)->create(['email' => 'admin@prilov.dev']);
        factory(App\User::class)->states(['profile'])->create(
            ['email' => 'seller@prilov.dev', 'shipping_method_ids' => [1]]
        );
        factory(App\User::class)->create(['email' => 'normal@prilov.dev']);
        factory(App\User::class, 5)->create();
        factory(App\User::class, 5)->states(['profile'])->create();

        App\User::where('id', 1)->first()->assignRole('admin');

        factory(App\Address::class, 20)->create();

        factory(App\Banner::class, 10)->create();
        factory(App\Slider::class, 10)->create();

        factory(App\Brand::class, 5)->create();
        factory(App\Campaign::class, 5)->create();
        factory(App\Category::class, 3)->create();
        factory(App\Category::class, 5)->states(['subcategory'])->create();
        factory(App\Size::class, 3)->create();
        factory(App\Size::class, 5)->states(['child'])->create();
        factory(App\Color::class, 5)->create();
        factory(App\Condition::class, 3)->create();
        factory(App\Product::class, 20)->create();

        factory(App\Menu::class, 3)->create();
        factory(App\MenuItem::class, 3)->create();
        factory(App\MenuItem::class, 10)->states(['children'])->create();

        factory(App\CreditsTransaction::class, 30)->create();

        factory(App\Coupon::class, 5)->states(['random'])->create();
        factory(App\Coupon::class, 5)->states(['%', 'random'])->create();

        Artisan::call('passport:client', ['--personal' => true, '-n' => true]);
    }
}
