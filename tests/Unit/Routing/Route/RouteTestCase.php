<?php

namespace Tests\Unit\Routing\Route;

use ApiFramework\Contracts\Routing\Route\RouteInterface;
use Tests\Unit\TestCase;

class RouteTestCase extends TestCase
{
    /**
     * @test
     */
    public function it_constructs_the_route_callback_properties_properly(): void
    {
        $router = $this->makeRouter();
        /** @var RouteInterface */
        $route = $router->get('/', 'DummyController@method1', ['middleware' => 'DummyMiddleware']);

        $this->assertEquals('DummyController@method1', $route->getCallback());
        $this->assertEquals('DummyController', $route->getClass());
        $this->assertEquals('method1', $route->getMethod());
        $this->assertEquals('DummyMiddleware', $route->getMiddlewares()[0]);
    }
}
