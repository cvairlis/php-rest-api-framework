<?php

namespace Tests\Dummies;

use ApiFramework\Contracts\Http\Middleware\MiddlewareInterface;
use ApiFramework\Http\Request;

class DummyMiddleware implements MiddlewareInterface
{
    /**
     * @param Request $request
     */
    public function handle(Request $request): void
    {
        $request->dummy_middleware = true;
    }
}
