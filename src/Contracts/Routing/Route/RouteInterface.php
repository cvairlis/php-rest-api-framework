<?php

namespace ApiFramework\Contracts\Routing\Route;

use ApiFramework\Http\Request;
use ApiFramework\Routing\Router\Router;

interface RouteInterface
{
    /**
     * Method called to check if a domain matches
     *
     * @param string $url
     * @param Request $request
     * @return bool
     */
    public function matchRoute(string $url, Request $request): bool;

    /**
     * Called when route is matched.
     * Returns class to be rendered.
     *
     * @param Request $request
     * @param Router $router
     * @return string
     */
    public function renderRoute(Request $request, Router $router): ?string;

    /**
     * Set allowed request methods
     *
     * @param array $methods
     * @return static
     */
    public function setRequestMethods(array $methods): self;

    /**
     * Get allowed request methods
     *
     * @return array
     */
    public function getRequestMethods(): array;

    /**
     * Set callback
     *
     * @param string|array|\Closure $callback
     * @return static
     */
    public function setCallback($callback): self;

    /**
     * @return string|callable
     */
    public function getCallback();

    /**
     * Return active method
     *
     * @return string|null
     */
    public function getMethod(): ?string;

    /**
     * Set active method
     *
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): self;

    /**
     * Get class
     *
     * @return string|null
     */
    public function getClass(): ?string;

    /**
     * @param string $namespace
     * @return static
     */
    public function setNamespace(string $namespace): self;

    /**
     * @return string|null
     */
    public function getNamespace(): ?string;

    /**
     * Get parameter names.
     *
     * @return array
     */
    public function getWhere(): array;

    /**
     * Set parameter names.
     *
     * @param array $options
     * @return static
     */
    public function setWhere(array $options): self;

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParameters(): array;

    /**
     * Get parameters
     *
     * @param array $parameters
     * @return static
     */
    public function setParameters(array $parameters): self;

    /**
     * Merge with information from another route.
     *
     * @param array $settings
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $settings, bool $merge = false): self;

    /**
     * Get middlewares array
     *
     * @return array
     */
    public function getMiddlewares(): array;

    /**
     * Set middleware class-name
     *
     * @param string $middleware
     * @return static
     */
    public function addMiddleware(string $middleware): self;

    /**
     * Set middlewares array
     *
     * @param array $middlewares
     * @return static
     */
    public function setMiddlewares(array $middlewares): self;
}
