<?php

namespace ApiFramework\Contracts\Http\Middleware;

use ApiFramework\Http\Request;

interface MiddlewareInterface
{
    /**
     * @param Request $request
     */
    public function handle(Request $request): void;
}
