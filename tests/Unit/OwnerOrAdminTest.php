<?php

namespace Tests\Feature;

use App\Address;
use App\Http\Middleware\OwnerOrAdmin;
use App\User;
use Mockery;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OwnerOrAdminTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->middleware = new OwnerOrAdmin();
        $this->closure = function ($return) {
            return $return;
        };
        $this->route = Mockery::mock();
        $this->users = factory(User::class, 2)->create();
        $this->request = Mockery::mock([
            'route' => $this->route,
        ]);
    }

    public function testAccessDeniedForGuests()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Must be someone.');
        $this->middleware->handle($this->request, $this->closure);
    }

    public function testAccessDeniedForOtherUser()
    {
        auth()->setUser($this->users[0]);

        $this->route->parameters = [$this->users[1]];

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('User must be owner or admin.');
        $this->middleware->handle($this->request, $this->closure);
    }

    public function testAccessDeniedForNotOwner()
    {
        auth()->setUser($this->users[0]);

        $address = factory(Address::class)->make(['user_id' => $this->users[1]->id]);

        $this->route->parameters = [$address];

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('User must be owner or admin.');
        $this->middleware->handle($this->request, $this->closure);
    }

    public function testAccessAllowedForSameUser()
    {
        auth()->setUser($this->users[0]);

        $this->route->parameters = [$this->users[0]];
        $response = $this->middleware->handle($this->request, $this->closure);

        $this->assertEquals($response, $this->request);
    }

    public function testAccessAllowedForOwner()
    {
        auth()->setUser($this->users[0]);

        $address = factory(Address::class)->make(['user_id' => $this->users[0]->id]);
        $this->route->parameters = [$address];
        $response = $this->middleware->handle($this->request, $this->closure);

        $this->assertEquals($response, $this->request);
    }

    public function testAccessAllowedForAdmin()
    {
        auth()->setUser($this->users[0]);
        Role::create(['name' => 'admin']);

        $this->users[0]->assignRole('admin');
        $address = factory(Address::class)->make(['user_id' => $this->users[1]->id]);
        $this->route->parameters = [$address];
        $response = $this->middleware->handle($this->request, $this->closure);

        $this->assertEquals($response, $this->request);
    }
}
