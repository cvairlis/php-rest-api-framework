<?php

namespace ApiFramework\Routing\Router;

use Exception;
use ApiFramework\Http\Request;
use ApiFramework\Contracts\Filesystem\FilesystemInterface;
use ApiFramework\Contracts\Routing\Route\LoadableRouteInterface;
use ApiFramework\Contracts\Routing\Route\RouteInterface;
use ApiFramework\Filesystem\Filesystem;
use ApiFramework\Contracts\Routing\Router\RouterInterface;

class Router implements RouterInterface
{
    /**
     * Current request
     * @var Request
     */
    protected $request;

    /**
     * All added routes
     * @var array
     */
    protected $routes = [];

    /**
     * List of processed routes
     * @var array|LoadableRouteInterface[]
     */
    protected $processedRoutes = [];

    /**
     * Filesystem instance
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * When enabled the router will render all routes that matches.
     * When disabled the router will stop execution when first route is found.
     * @var bool
     */
    protected $renderMultipleRoutes = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->filesystem = new Filesystem();
    }

    /**
     * Add route
     * @param RouteInterface $route
     * @return RouteInterface
     */
    public function addRoute(RouteInterface $route): RouteInterface
    {
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Process added routes.
     *
     * @param array|RouteInterface[] $routes
     */
    protected function processRoutes(array $routes): void
    {
        // Loop through each route-request
        foreach ($routes as $route) {
            if ($route instanceof LoadableRouteInterface === true) {

                /* Add the route to the map, so we can find the active one when all routes has been loaded */
                $this->processedRoutes[] = $route;
            }
        }
    }

    /**
     * Start the routing
     *
     * @return string|null
     * @throws Exception
     */
    public function start(): ?string
    {
        /* Loop through each route-request */
        $this->processRoutes($this->routes);
        $output = $this->routeRequest();

        return $output;
    }

    /**
     * Routes the request
     *
     * @return string|null
     * @throws Exception
     */
    public function routeRequest(): ?string
    {
        $url = $this->request->getRewriteUrl() ?? $this->request->getUrl()->getPath();

        foreach ($this->processedRoutes as $key => $route) {
            /* If the route matches */
            if ($route->matchRoute($url, $this->request) === true) {
                $route->loadMiddleware($this->request, $this);
                $output = $this->handleRouteRewrite($key, $url);
                if ($output !== null) {
                    return $output;
                }
                $this->request->addLoadedRoute($route);

                $routeOutput = $route->renderRoute($this->request, $this);

                if ($this->renderMultipleRoutes === true) {
                    if ($routeOutput !== null) {
                        return $routeOutput;
                    }

                    $output = $this->handleRouteRewrite($key, $url);
                    if ($output !== null) {
                        return $output;
                    }
                } else {
                    $output = $this->handleRouteRewrite($key, $url);

                    return $output ?? $routeOutput;
                }
            }
        }

        return null;
    }

    /**
     * Handle route-rewrite
     *
     * @param string $key
     * @param string $url
     * @return string|null
     * @throws Exception
     */
    protected function handleRouteRewrite(string $key, string $url): ?string
    {
        /* If the request has changed */
        if ($this->request->hasPendingRewrite() === false) {
            return null;
        }

        $route = $this->request->getRewriteRoute();

        if ($route !== null) {
            /* Add rewrite route */
            $this->processedRoutes[] = $route;
        }

        if ($this->request->getRewriteUrl() !== $url) {
            unset($this->processedRoutes[$key]);

            $this->request->setHasPendingRewrite(false);

            return $this->routeRequest();
        }

        return null;
    }

    /**
     * Get routes that has been processed.
     *
     * @return array
     */
    public function getProcessedRoutes(): array
    {
        return $this->processedRoutes;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Set routes
     *
     * @param array $routes
     * @return static
     */
    public function setRoutes(array $routes): RouterInterface
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Get current request
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Set filesystem
     *
     * @param FilesystemInterface $filesystem
     */
    public function setFilesystem(FilesystemInterface $filesystem): void
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Get filesystem
     *
     * @return FilesystemInterface
     */
    public function getFilesystem(): FilesystemInterface
    {
        return $this->filesystem;
    }
}
