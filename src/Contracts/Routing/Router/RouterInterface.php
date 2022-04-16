<?php

namespace ApiFramework\Contracts\Routing\Router;

use Exception;
use ApiFramework\Http\Request;
use ApiFramework\Contracts\Filesystem\FilesystemInterface;
use ApiFramework\Contracts\Routing\Route\RouteInterface;

interface RouterInterface
{
    /**
     * Add route
     * @param RouteInterface $route
     * @return RouteInterface
     */
    public function addRoute(RouteInterface $route): RouteInterface;

    /**
     * Start the routing
     *
     * @return string|null
     * @throws Exception
     */
    public function start(): ?string;

    /**
     * Routes the request
     *
     * @return string|null
     * @throws Exception
     */
    public function routeRequest(): ?string;

    /**
     * Get routes that has been processed.
     *
     * @return array
     */
    public function getProcessedRoutes(): array;

    /**
     * @return array
     */
    public function getRoutes(): array;

    /**
     * Set routes
     *
     * @param array $routes
     * @return static
     */
    public function setRoutes(array $routes): self;

    /**
     * Get current request
     *
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * Set filesystem
     *
     * @param FilesystemInterface $filesystem
     */
    public function setFilesystem(FilesystemInterface $filesystem): void;

    /**
     * Get filesystem
     *
     * @return FilesystemInterface
     */
    public function getFilesystem(): FilesystemInterface;
}
