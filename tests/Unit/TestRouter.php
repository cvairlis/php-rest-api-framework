<?php

namespace Tests\Unit;

use ApiFramework\Routing\Router\BasicRouter;

class TestRouter extends BasicRouter
{
    public function __construct()
    {
        static::request()->setHost('router-test.local');
    }

    public static function run(string $test_url = '/', string $test_method = 'get'): void
    {
        $request = static::request();
        $request->setUrl((new \ApiFramework\Http\Url($test_url))->setHost('router-test.local'));
        $request->setMethod($test_method);
        static::start();
    }
}
