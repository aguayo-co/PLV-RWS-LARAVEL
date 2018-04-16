<?php

namespace Tests\Feature;

use App\Brand;
use App\Campaign;
use App\Category;
use App\Color;
use App\Condition;
use App\Product;
use App\ShippingMethod;
use App\Size;
use App\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTest extends TestCase
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

    protected function getProduct($status)
    {
        return factory(Product::class)->create(['status' => $status, 'user_id' => $this->seller->id]);
    }

    public function testCreateProductIsProtected()
    {
        $middleware = app()->routes->getByName('api.product.create')->gatherMiddleware();
        $this->assertContains('role:seller|admin', $middleware);
    }

    public function testProductIndexFiltersUnapproved()
    {
        $this->getProduct(Product::STATUS_HIDDEN);
        $url = route('api.products');

        $response = $this->json('GET', $url);
        $response
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function testProductIndexIsUnfilteredForAdmin()
    {
        $product = $this->getProduct(Product::STATUS_HIDDEN);
        $url = route('api.products');

        $response = $this->actingAs($this->admin)->json('GET', $url);
        $response
            ->assertStatus(200)
            ->assertJson(['data' => [['id' => $product->id]]]);
    }

    public function testProductIndexIsUnfilteredForOwner()
    {
        $user2 = factory(User::class)->states(['profile'])->create();

        factory(Product::class)->create(['status' => Product::STATUS_HIDDEN, 'user_id' => $this->seller]);
        $product = factory(Product::class)->create(['status' => Product::STATUS_HIDDEN, 'user_id' => $user2->id]);

        $url = route('api.products');

        $response = $this->actingAs($user2)->json('GET', $url);
        $response
            ->assertStatus(200)
            ->assertJson(['data' => [['id' => $product->id]]])
            ->assertJsonCount(1, 'data');
    }

    public function testStatusChangeIsProtected()
    {
        $product = $this->getProduct(Product::STATUS_HIDDEN);

        $url = route('api.product.update', $product);

        $response = $this->actingAs($this->seller)->json('PATCH', $url, ['status' => Product::STATUS_APPROVED]);
        $response->assertStatus(403);
    }

    public function testStatusChangeIsAllowedAfterApproved()
    {
        $product = $this->getProduct(Product::STATUS_APPROVED);

        $url = route('api.product.update', $product);

        $response = $this->actingAs($this->seller)->json('PATCH', $url, ['status' => Product::STATUS_AVAILABLE]);
        $response->assertStatus(200);
    }

    public function testStatusChangeIsRestricted()
    {
        $product = $this->getProduct(Product::STATUS_APPROVED);

        $url = route('api.product.update', $product);

        $response = $this->actingAs($this->seller)->json('PATCH', $url, ['status' => Product::STATUS_HIDDEN]);
        $response->assertStatus(403)
            ->assertSee('Only an admin can set the given status.');
    }

    public function testNewProductIsUnpublished()
    {
        $url = route('api.product.create');

        $productData = factory(Product::class)->raw();
        unset($productData['status']);
        unset($productData['user_id']);

        $response = $this->actingAs($this->seller)
            ->withHeaders(['accept' => 'application/json'])->post($url, $productData);
        $response->assertStatus(200)->assertJson(['status' => Product::STATUS_UNPUBLISHED]);
    }

    public function testNewProductStatusIsProtected()
    {
        $url = route('api.product.create');

        $productData = factory(Product::class)->raw();
        $productData['status'] = Product::STATUS_AVAILABLE;
        unset($productData['user_id']);

        $response = $this->actingAs($this->seller)
            ->withHeaders(['accept' => 'application/json'])->post($url, $productData);
        $response->assertStatus(403)
            ->assertSee('Only admin can change status.');
    }
}
