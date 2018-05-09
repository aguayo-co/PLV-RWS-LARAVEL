<?php

namespace Tests\Feature;

use App\Address;
use App\Brand;
use App\Campaign;
use App\Category;
use App\Color;
use App\Condition;
use App\Product;
use App\ShippingMethod;
use App\Size;
use App\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersVacationModeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        factory(Brand::class)->create();
        factory(Campaign::class)->create();
        factory(Category::class)->create();
        factory(Category::class)->states(['subcategory'])->create();
        factory(Color::class, 2)->create();
        factory(Condition::class)->create();
        factory(ShippingMethod::class)->create();
        factory(Size::class)->create();
        factory(Size::class)->states(['child'])->create();
        Role::create(['name' => 'seller']);

        $this->seller = factory(User::class)->states(['profile'])->create()->fresh();
        $this->artisan('db:seed', ['--class' => 'GeonamesSeeder']);
        $this->address = factory(Address::class)->create(['user_id' => $this->seller->id]);
        $this->seller = $this->seller->fresh();
    }

    protected function getProduct($status)
    {
        return factory(Product::class)->create(['status' => $status, 'user_id' => $this->seller->id]);
    }

    public function testProductsAreSetToVacationStatus()
    {
        $productVacation = $this->getProduct(Product::STATUS_AVAILABLE);
        $productUnavailable = $this->getProduct(Product::STATUS_UNAVAILABLE);
        $url = route('api.user.update', $this->seller);
        $this->actingAs($this->seller)->json('PATCH', $url, [
            'vacation_mode' => true
        ]);
        $productVacation = $productVacation->fresh();
        $productUnavailable = $productUnavailable->fresh();
        $this->assertEquals($productVacation->status, Product::STATUS_ON_VACATION);
        $this->assertEquals($productUnavailable->status, Product::STATUS_UNAVAILABLE);
    }

    public function testProductsAreRemovedFromVacationStatus()
    {
        $productVacation = $this->getProduct(Product::STATUS_ON_VACATION);
        $productUnavailable = $this->getProduct(Product::STATUS_UNAVAILABLE);
        $url = route('api.user.update', $this->seller);
        $this->actingAs($this->seller)->json('PATCH', $url, [
            'vacation_mode' => false
        ]);
        $productVacation = $productVacation->fresh();
        $productUnavailable = $productUnavailable->fresh();
        $this->assertEquals($productVacation->status, Product::STATUS_AVAILABLE);
        $this->assertEquals($productUnavailable->status, Product::STATUS_UNAVAILABLE);
    }
}
