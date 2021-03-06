<?php

namespace Tests\Feature;

use App\Address;
use App\Brand;
use App\Campaign;
use App\Category;
use App\Color;
use App\Condition;
use App\Coupon;
use App\Group;
use App\Product;
use App\ShippingMethod;
use App\Size;
use App\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderCouponTest extends TestCase
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
        $this->user = factory(User::class)->create()->fresh();
    }

    protected function createProduct($options = [])
    {
        return factory(Product::class)->create(
            ['status' => Product::STATUS_APPROVED, 'user_id' => $this->seller->id] + $options
        );
    }

    public function testCouponIsApplied()
    {
        $product = $this->createProduct();
        $coupon = factory(Coupon::class)->create();
        $url = route('api.shopping_cart.update');
        $response = $this->actingAs($this->user)
            ->json('PATCH', $url, [
                'add_product_ids' => [$product->id],
                'coupon_code' => $coupon->code,
            ]);

        $response->assertStatus(200)
            ->assertJson(['due' => max(0, $product->price - $coupon->discount_value)]);
    }

    public function testCouponWithPercentageIsApplied()
    {
        $product = $this->createProduct();
        $coupon = factory(Coupon::class)->states(['%'])->create();
        $url = route('api.shopping_cart.update');
        $response = $this->actingAs($this->user)
            ->json('PATCH', $url, [
                'add_product_ids' => [$product->id],
                'coupon_code' => $coupon->code,
            ]);

        $discount = round($product->price * $coupon->discount_value / 100);
        $due = $product->price - $discount;
        $response->assertStatus(200)
            ->assertJson(['due' => $due]);
        $response->assertStatus(200)
            ->assertJson(['coupon_discount' => $discount]);
    }

    public function testCouponBrandFilters()
    {
        $product = $this->createProduct();
        $brand = factory(Brand::class)->create();
        $coupon = factory(Coupon::class)->create(['brands_ids' => [$brand->id]]);
        $url = route('api.shopping_cart.update');
        $response = $this->actingAs($this->user)
            ->json('PATCH', $url, [
                'add_product_ids' => [$product->id],
                'coupon_code' => $coupon->code,
            ]);

        $response->assertStatus(200)
            ->assertJson(['due' => $product->price]);
        $response->assertStatus(200)
            ->assertJson(['coupon_discount' => 0]);
    }

    public function testCouponCampaignFilters()
    {
        $product = $this->createProduct();
        $campaign = factory(Campaign::class)->create();
        $coupon = factory(Coupon::class)->create(['campaigns_ids' => [$campaign->id]]);
        $url = route('api.shopping_cart.update');
        $response = $this->actingAs($this->user)
            ->json('PATCH', $url, [
                'add_product_ids' => [$product->id],
                'coupon_code' => $coupon->code,
            ]);

        $response->assertStatus(200)
            ->assertJson(['due' => $product->price]);
        $response->assertStatus(200)
            ->assertJson(['coupon_discount' => 0]);
    }

    public function testCouponMinimumCommissionFilters()
    {
        $product = $this->createProduct(['commission' => 20]);
        $coupon = factory(Coupon::class)->create(['minimum_commission' => 21]);
        $url = route('api.shopping_cart.update');
        $response = $this->actingAs($this->user)
            ->json('PATCH', $url, [
                'add_product_ids' => [$product->id],
                'coupon_code' => $coupon->code,
            ]);

        $response->assertStatus(200)
            ->assertJson(['due' => $product->price]);
        $response->assertStatus(200)
            ->assertJson(['coupon_discount' => 0]);
    }

    public function testCouponMinimumPriceFilters()
    {
        $product = $this->createProduct(['price' => 2000]);
        $coupon = factory(Coupon::class)->create(['minimum_price' => 2001]);
        $url = route('api.shopping_cart.update');
        $response = $this->actingAs($this->user)
            ->json('PATCH', $url, [
                'add_product_ids' => [$product->id],
                'coupon_code' => $coupon->code,
            ]);

        $response->assertStatus(200)
            ->assertJson(['due' => $product->price]);
        $response->assertStatus(200)
            ->assertJson(['coupon_discount' => 0]);
    }

    public function testCouponDiscountedProductFilters()
    {
        $product = $this->createProduct();
        $coupon = factory(Coupon::class)->create();
        $group = factory(Group::class)->states(['with_discount'])->create();
        $group->users()->sync([$product->user_id]);
        $url = route('api.shopping_cart.update');
        $response = $this->actingAs($this->user)
            ->json('PATCH', $url, [
                'add_product_ids' => [$product->id],
                'coupon_code' => $coupon->code,
            ]);

        $response->assertStatus(200)
            ->assertJson(['due' => $product->sale_price]);
        $response->assertStatus(200)
            ->assertJson(['coupon_discount' => 0]);
    }
}
