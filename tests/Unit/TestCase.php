<?php

namespace Tests\Unit;

use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function makeRouter()
    {
        return new TestRouter();
    }
}
