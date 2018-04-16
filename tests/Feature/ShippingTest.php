<?php

namespace Tests\Feature;

use Tests\TestCase;

class ShippingTest extends TestCase
{
    public function testCreateShippingMethodIsProtected()
    {
        $middleware = app()->routes->getByName('api.shipping_method.create')->gatherMiddleware();
        $this->assertContains('role:admin', $middleware);
    }
}
