<?php

namespace ApiFramework\Routing\Route;

use ApiFramework\Http\Request;

class RouteUrl extends LoadableRoute
{
    /**
     * RouteUrl constructor.
     * @param string $url
     * @param \Closure|string $callback
     */
    public function __construct(string $url, $callback)
    {
        $this->setUrl($url);
        $this->setCallback($callback);
    }

    public function matchRoute(string $url, Request $request): bool
    {
        /* Parse parameters from current route */
        $parameters = $this->parseParameters($this->url, $url);
        /* If no parameters was found on this route, we stop */
        if ($parameters === null) {
            return false;
        }
        /* Set the parameters */
        $this->setParameters((array)$parameters);

        return true;
    }
}
