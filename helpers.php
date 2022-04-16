<?php

use ApiFramework\Routing\Router\BasicRouter as Router;
use ApiFramework\Http\Response;
use ApiFramework\Http\Request;

/**
 * @return \ApiFramework\Http\Response
 */
function response(): Response
{
    return Router::response();
}

/**
 * @return \ApiFramework\Http\Request
 */
function request(): Request
{
    return Router::request();
}

/**
 * @param string $url
 * @param int|null $code
 */
function redirect(string $url, ?int $code = null): void
{
    if ($code !== null) {
        response()->httpCode($code);
    }

    response()->redirect($url);
}
