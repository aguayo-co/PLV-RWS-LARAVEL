<?php

namespace Tests\Feature;

use App\User;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Password;
use App\ShippingMethod;

class UsersTest extends TestCase
{
    public function testRegisterMustBeGuest()
    {
        $middleware = app()->routes->getByName('api.register')->gatherMiddleware();
        $this->assertContains('guest', $middleware);
    }

    public function testLoginMustBeGuest()
    {
        $middleware = app()->routes->getByName('api.login')->gatherMiddleware();
        $this->assertContains('guest', $middleware);
    }

    public function testPasswordRecoveryEmailMustBeGuest()
    {
        $middleware = app()->routes->getByName('api.password.recovery.email')->gatherMiddleware();
        $this->assertContains('guest', $middleware);
    }

    public function testPasswordRecoveryTokenMustBeGuest()
    {
        $middleware = app()->routes->getByName('api.password.recovery.token')->gatherMiddleware();
        $this->assertContains('guest', $middleware);
    }

    public function testUserUpdateIsProtected()
    {
        $middleware = app()->routes->getByName('api.user.update')->gatherMiddleware();
        $this->assertContains('auth:api', $middleware);
        $this->assertContains('owner_or_admin', $middleware);
    }

    public function testUserAddressesIsProtected()
    {
        $middleware = app()->routes->getByName('api.user.addresses.get')->gatherMiddleware();
        $this->assertContains('auth:api', $middleware);
    }

    public function testCreateUserAddressIsProtected()
    {
        $middleware = app()->routes->getByName('api.user.address.create')->gatherMiddleware();
        $this->assertContains('auth:api', $middleware);
        $this->assertContains('self_or_admin', $middleware);
    }

    public function testTokenIsSentOnCreate()
    {
        $this->artisan('passport:client', ['--personal' => true, '-n' => true]);

        $url = route('api.register');
        $response = $this->json('POST', $url, [
            'email' => 'test@domain.com',
            'password' => '123$%ˆ456!@#',
            'first_name' => 'First',
            'last_name' => 'Last',
        ]);
        $response->assertJsonStructure(['api_token']);
    }

    public function testTokenIsSentOnLogin()
    {
        $this->artisan('passport:client', ['--personal' => true, '-n' => true]);
        $user = factory(User::class)->create();

        $url = route('api.login');
        $response = $this->json('POST', $url, [
            'email' => $user->email,
            'password' => 'secret',
        ]);
        $response->assertJsonStructure(['api_token']);
    }

    public function testTokenIsSentOnPasswordChange()
    {
        $this->artisan('passport:client', ['--personal' => true, '-n' => true]);

        $user = factory(User::class)->create();
        $headers = [
            'Authorization' => 'Bearer ' . $user->createToken('PrilovRegister')->accessToken
        ];

        $url = route('api.user.update', $user->id);
        $response = $this->json('PATCH', $url, [
            'password' => '123$%ˆ456!@#',
        ], $headers);

        $response->assertJsonStructure(['api_token']);
    }

    public function testTokenIsSentOnPasswordReset()
    {
        $this->artisan('passport:client', ['--personal' => true, '-n' => true]);

        $user = factory(User::class)->create();

        $url = route('api.password.reset', $user->email);
        $response = $this->json('POST', $url, [
            'password' => '123$%ˆ456!@#',
            'token' => Password::broker()->getRepository()->create($user)
        ]);

        $response->assertJsonStructure(['api_token']);
    }

    public function testTokenIsNotSentOnUpdate()
    {
        $user = factory(User::class)->create();

        Passport::actingAs(
            $user
        );

        $url = route('api.user.update', $user->id);
        $response = $this->json('PATCH', $url, [
            'first_name' => 'First Name',
        ]);

        $response->assertStatus(200)->assertJsonMissing(['api_token']);
    }

    public function testTokenIsNotSentOnGet()
    {
        $user = factory(User::class)->create();

        $url = route('api.user.get', $user->id);
        $response = $this->json('GET', $url);

        $response->assertStatus(200)->assertJsonMissing(['api_token']);
    }

    public function testDoesNotHaveSellerRole()
    {
        Role::create(['name' => 'seller']);
        $user = factory(User::class)->create();
        $this->assertFalse($user->hasRole('seller'));
    }

    public function testSellerRoleIsAdded()
    {
        Role::create(['name' => 'seller']);
        factory(ShippingMethod::class)->create();
        $user = factory(User::class)->states('profile')->create();
        $this->assertTrue($user->hasRole('seller'));
    }

    public function testSellerRoleIsRemoved()
    {
        Role::create(['name' => 'seller']);
        factory(ShippingMethod::class)->create();
        $user = factory(User::class)->states('profile')->create();
        $user->shipping_method_ids = [];
        $user->save();
        $this->assertFalse($user->hasRole('seller'));
    }
}
