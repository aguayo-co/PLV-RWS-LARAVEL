<?php

namespace Tests\Feature;

use Tests\TestCase;

class MenusTest extends TestCase
{
    public function testCreateMenuIsProtected()
    {
        $middleware = app()->routes->getByName('api.menu.create')->gatherMiddleware();
        $this->assertContains('role:admin', $middleware);
    }

    public function testCreateMenuItemIsProtected()
    {
        $middleware = app()->routes->getByName('api.menu_item.create')->gatherMiddleware();
        $this->assertContains('role:admin', $middleware);
    }
}
