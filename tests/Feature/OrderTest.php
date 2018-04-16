<?php

namespace Tests\Feature;

use App\Brand;
use App\Campaign;
use App\Category;
use App\Color;
use App\Condition;
use App\Product;
use App\Sale;
use App\ShippingMethod;
use App\Size;
use App\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderTest extends TestCase
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
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'seller']);

        $this->admin = factory(User::class)->create();
        $this->admin->assignRole('admin');
        $this->admin = $this->admin->fresh();
        $this->seller = factory(User::class)->states(['profile'])->create()->fresh();
        $this->user = factory(User::class)->create()->fresh();
    }

    protected function createProduct($status)
    {
        return factory(Product::class)->create(['status' => $status, 'user_id' => $this->seller->id]);
    }

    public function testShoppingCartNeedsSaleableProducts()
    {
        $product = $this->createProduct(Product::STATUS_UNAVAILABLE);

        $url = route('api.shopping_cart.update');

        $response = $this->actingAs($this->user)->json('PATCH', $url, ['add_product_ids' => [$product->id]]);
        $response->assertStatus(422);
    }

    public function testShoppingReceivesProducts()
    {
        $product = $this->createProduct(Product::STATUS_APPROVED);

        $url = route('api.shopping_cart.update');

        $response = $this->actingAs($this->user)->json('PATCH', $url, ['add_product_ids' => [$product->id]]);
        $response->assertStatus(200)
            ->assertJson(['sales' => [['products'=> [['id' => $product->id]]]]]);
    }

    public function testMarkingSaleAsReceivedIsValidated()
    {
        $product = $this->createProduct(Product::STATUS_APPROVED);
        $url = route('api.shopping_cart.update');
        $responseData = $this->actingAs($this->user)
            ->json('PATCH', $url, ['add_product_ids' => [$product->id]])->decodeResponseJson();

        $saleId = $responseData['sales'][0]['id'];
        $requestData = [
            'sales' => [
                $saleId => [
                    'status' => Sale::STATUS_RECEIVED,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->json('PATCH', $url, $requestData);
        $response->assertStatus(422)
            ->assertJsonFragment(['La orden no ha sido pagada.']);
    }
}
