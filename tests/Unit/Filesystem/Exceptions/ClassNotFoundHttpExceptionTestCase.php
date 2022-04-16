<?php

namespace Tests\Unit\Filesystem\Exceptions;

use ApiFramework\Filesystem\Exceptions\ClassNotFoundHttpException;
use Tests\Unit\TestCase;

class ClassNotFoundHttpExceptionTestCase extends TestCase
{
    /**
     * @test
     */
    public function it_throws_when_middleware_class_does_not_exist(): void
    {
        $this->expectException(ClassNotFoundHttpException::class);
        $router = $this->makeRouter();
        $router->get('/', 'DummyController@method1', ['middleware' => 'DummyMiddleware']);
        $router->run();
    }
}
