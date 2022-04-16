<?php

namespace ApiFramework\Routing\Router;

use Closure;
use Exception;
use ApiFramework\Http\Request;
use ApiFramework\Http\Response;
use ApiFramework\Contracts\Routing\Route\RouteInterface;
use ApiFramework\Routing\Route\RouteUrl;

class BasicRouter
{
    /**
     * Router instance
     * @var Router
     */
    protected static $router;

    /**
     * The response object
     * @var Response
     */
    protected static $response;

    /**
     * Start routing
     *
     * @throws Exception
     */
    public static function start(): void
    {
        echo static::router()->start();
    }

    /**
     * Redirect to when route matches.
     *
     * @param string $where
     * @param string $to
     * @param int $httpCode
     * @return RouteInterface
     */
    public static function redirect(string $where, string $to, int $httpCode = 301): RouteInterface
    {
        return static::get($where, static function () use ($to, $httpCode): void {
            static::response()->redirect($to, $httpCode);
        });
    }

    /**
     * Route the given url to your callback on GET request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     *
     * @return RouteUrl|RouteInterface
     */
    public static function get(string $url, $callback, array $settings = null): RouteInterface
    {
        return static::match([Request::REQUEST_TYPE_GET], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on POST request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|RouteInterface
     */
    public static function post(string $url, $callback, array $settings = null): RouteInterface
    {
        return static::match([Request::REQUEST_TYPE_POST], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on PUT request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|RouteInterface
     */
    public static function put(string $url, $callback, array $settings = null): RouteInterface
    {
        return static::match([Request::REQUEST_TYPE_PUT], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on PATCH request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|RouteInterface
     */
    public static function patch(string $url, $callback, array $settings = null): RouteInterface
    {
        return static::match([Request::REQUEST_TYPE_PATCH], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on DELETE request method.
     *
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|RouteInterface
     */
    public static function delete(string $url, $callback, array $settings = null): RouteInterface
    {
        return static::match([Request::REQUEST_TYPE_DELETE], $url, $callback, $settings);
    }

    /**
     * This type will route the given url to your callback on the provided request methods.
     *
     * @param array $requestMethods
     * @param string $url
     * @param string|array|Closure $callback
     * @param array|null $settings
     * @return RouteUrl|RouteInterface
     */
    public static function match(array $requestMethods, string $url, $callback, array $settings = null): RouteInterface
    {
        $route = new RouteUrl($url, $callback);
        $route->setRequestMethods($requestMethods);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        return static::router()->addRoute($route);
    }

    /**
     * Get the request
     *
     * @return Request
     */
    public static function request(): Request
    {
        return static::router()->getRequest();
    }

    /**
     * Get the response object
     *
     * @return Response
     */
    public static function response(): Response
    {
        if (static::$response === null) {
            static::$response = new Response(static::request());
        }

        return static::$response;
    }

    /**
     * Returns the router instance
     *
     * @return Router
     */
    public static function router(): Router
    {
        if (static::$router === null) {
            static::$router = new Router();
        }

        return static::$router;
    }
}
