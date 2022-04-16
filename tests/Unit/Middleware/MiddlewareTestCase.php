<?php

namespace Tests\Unit\Middleare;

use Tests\Dummies\DummyController;
use Tests\Dummies\DummyMiddleware;
use Tests\Unit\TestCase;

class MiddlewareTestCase extends TestCase
{
    /**
     * @test
     */
    public function it_passes_through_the_middleware(): void
    {
        $router = $this->makeRouter();
        $router->get('/', DummyController::class . '@index', ['middleware' => DummyMiddleware::class]);
        $router->run();

        $this->assertTrue($router->request()->dummy_middleware);
    }
}
