<?php

namespace Tests\Feature;

use Tests\TestCase;

class BannerTest extends TestCase
{
    public function testCreateBannerIsProtected()
    {
        $middleware = app()->routes->getByName('api.banner.create')->gatherMiddleware();
        $this->assertContains('role:admin', $middleware);
    }
}
