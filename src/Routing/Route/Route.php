<?php

namespace ApiFramework\Routing\Route;

use ApiFramework\Contracts\Routing\Route\RouteInterface;
use ApiFramework\Http\Request;
use ApiFramework\Filesystem\Exceptions\ClassNotFoundHttpException;
use ApiFramework\Routing\Exceptions\NotFoundHttpException;
use ApiFramework\Routing\Router\Router;

abstract class Route implements RouteInterface
{
    protected const PARAMETERS_REGEX_FORMAT = '%s([\w]+)(\%s?)%s';
    protected const PARAMETERS_DEFAULT_REGEX = '[\w-]+';

    /**
     * Default regular expression used for parsing parameters.
     * @var string|null
     */
    protected $defaultParameterRegex;
    protected $paramModifiers = '{}';
    protected $paramOptionalSymbol = '?';
    protected $urlRegex = '/^%s\/?$/u';
    protected $parent;
    /**
     * @var string|callable|null
     */
    protected $callback;

    /* Default options */
    protected $namespace;
    protected $requestMethods = [];
    protected $where = [];
    protected $parameters = [];
    protected $originalParameters = [];
    protected $middlewares = [];

    /**
     * Render route
     *
     * @param Request $request
     * @param Router $router
     * @return string|null
     * @throws NotFoundHttpException
     */
    public function renderRoute(Request $request, Router $router): ?string
    {
        $callback = $this->getCallback();

        if ($callback === null) {
            return null;
        }

        $parameters = $this->getParameters();

        $parameters = array_filter($parameters, static function ($var): bool {
            return ($var !== null);
        });

        /* Render callback function */
        if (is_callable($callback) === true) {

            /* Load class from type hinting */
            if (is_array($callback) === true && isset($callback[0], $callback[1]) === true) {
                $callback[0] = $router->getFilesystem()->loadClass($callback[0]);
            }

            /* When the callback is a function */

            return $router->getFilesystem()->loadClosure($callback, $parameters);
        }

        $controller = $this->getClass();
        $method = $this->getMethod();

        $namespace = $this->getNamespace();
        $className = ($namespace !== null && $controller[0] !== '\\') ? $namespace . '\\' . $controller : $controller;

        $class = $router->getFilesystem()->loadClass($className);

        if ($method === null) {
            $controller[1] = '__invoke';
        }

        if (method_exists($class, $method) === false) {
            throw new ClassNotFoundHttpException($className, $method, sprintf('Method "%s" does not exist in class "%s"', $method, $className), 404, null);
        }

        return $router->getFilesystem()->loadClassMethod($class, $method, $parameters);
    }

    protected function parseParameters($route, $url, $parameterRegex = null): ?array
    {
        $regex = (strpos($route, $this->paramModifiers[0]) === false) ? null :
            sprintf(
                static::PARAMETERS_REGEX_FORMAT,
                $this->paramModifiers[0],
                $this->paramOptionalSymbol,
                $this->paramModifiers[1]
            );

        // Ensures that host names/domains will work with parameters
        $url = '/' . ltrim($url, '/');
        $urlRegex = '';
        $parameters = [];

        if ($regex === null || (bool)preg_match_all('/' . $regex . '/u', $route, $parameters) === false) {
            $urlRegex = preg_quote($route, '/');
        } else {
            foreach (preg_split('/((-?\/?){[^}]+})/', $route) as $key => $t) {
                $regex = '';

                if ($key < count($parameters[1])) {
                    $name = $parameters[1][$key];

                    /* If custom regex is defined, use that */
                    if (isset($this->where[$name]) === true) {
                        $regex = $this->where[$name];
                    } else {
                        $regex = $parameterRegex ?? $this->defaultParameterRegex ?? static::PARAMETERS_DEFAULT_REGEX;
                    }

                    $regex = sprintf('((\/|-)(?P<%2$s>%3$s))%1$s', $parameters[2][$key], $name, $regex);
                }

                $urlRegex .= preg_quote($t, '/') . $regex;
            }
        }

        if (trim($urlRegex) === '' || (bool)preg_match(sprintf($this->urlRegex, $urlRegex), $url, $matches) === false) {
            return null;
        }

        $values = [];

        if (isset($parameters[1]) === true) {
            $lastParams = [];

            /* Only take matched parameters with name */
            foreach ((array)$parameters[1] as $name) {
                $values[$name] = (isset($matches[$name]) === true && $matches[$name] !== '') ? $matches[$name] : null;
            }

            $values = array_merge($values, $lastParams);
        }

        $this->originalParameters = $values;

        return $values;
    }

    /**
     * Set allowed request methods
     *
     * @param array $methods
     * @return static
     */
    public function setRequestMethods(array $methods): RouteInterface
    {
        $this->requestMethods = $methods;

        return $this;
    }

    /**
     * Get allowed request methods
     *
     * @return array
     */
    public function getRequestMethods(): array
    {
        return $this->requestMethods;
    }

    /**
     * Set callback
     *
     * @param string|array|\Closure $callback
     * @return static
     */
    public function setCallback($callback): RouteInterface
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return string|callable|null
     */
    public function getCallback()
    {
        return $this->callback;
    }

    public function getMethod(): ?string
    {
        if (is_array($this->callback) === true && count($this->callback) > 1) {
            return $this->callback[1];
        }

        if (is_string($this->callback) === true && strpos($this->callback, '@') !== false) {
            $tmp = explode('@', $this->callback);

            return $tmp[1];
        }

        return null;
    }

    public function getClass(): ?string
    {
        if (is_array($this->callback) === true && count($this->callback) > 0) {
            return $this->callback[0];
        }

        if (is_string($this->callback) === true && strpos($this->callback, '@') !== false) {
            $tmp = explode('@', $this->callback);

            return $tmp[0];
        }

        return null;
    }

    public function setMethod(string $method): RouteInterface
    {
        $this->callback = [$this->getClass(), $method];

        return $this;
    }

    public function setClass(string $class): RouteInterface
    {
        $this->callback = [$class, $this->getMethod()];

        return $this;
    }

    /**
     * @param string $namespace
     * @return static
     */
    public function setNamespace(string $namespace): RouteInterface
    {
        // Do not set namespace when class-hinting is used
        if (is_array($this->callback) === true) {
            return $this;
        }

        $ns = $this->getNamespace();

        if ($ns !== null) {
            // Don't overwrite namespaces that starts with \
            if ($ns[0] !== '\\') {
                $namespace .= '\\' . $ns;
            } else {
                $namespace = $ns;
            }
        }

        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Merge with information from another route.
     *
     * @param array $settings
     * @param bool $merge
     * @return static
     */
    public function setSettings(array $settings, bool $merge = false): RouteInterface
    {
        if (isset($settings['namespace']) === true) {
            $this->setNamespace($settings['namespace']);
        }

        if (isset($settings['method']) === true) {
            $this->setRequestMethods(array_merge($this->requestMethods, (array)$settings['method']));
        }

        if (isset($settings['where']) === true) {
            $this->setWhere(array_merge($this->where, (array)$settings['where']));
        }

        if (isset($settings['parameters']) === true) {
            $this->setParameters(array_merge($this->parameters, (array)$settings['parameters']));
        }

        // Push middleware if multiple
        if (isset($settings['middleware']) === true) {
            $this->setMiddlewares(array_merge((array)$settings['middleware'], $this->middlewares));
        }

        if (isset($settings['defaultParameterRegex']) === true) {
            $this->setDefaultParameterRegex($settings['defaultParameterRegex']);
        }

        return $this;
    }

    /**
     * Get parameter names.
     *
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Set parameter names.
     *
     * @param array $options
     * @return static
     */
    public function setWhere(array $options): RouteInterface
    {
        $this->where = $options;

        return $this;
    }

    /**
     * Add regular expression parameter match.
     * Alias for LoadableRoute::where()
     *
     * @param array $options
     * @return static
     * @see LoadableRoute::where()
     */
    public function where(array $options)
    {
        return $this->setWhere($options);
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function getParameters(): array
    {
        /* Sort the parameters after the user-defined param order, if any */
        $parameters = [];

        if (count($this->originalParameters) !== 0) {
            $parameters = $this->originalParameters;
        }

        return array_merge($parameters, $this->parameters);
    }

    /**
     * Get parameters
     *
     * @param array $parameters
     * @return static
     */
    public function setParameters(array $parameters): RouteInterface
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    /**
     * Add middleware class-name
     *
     * @param string $middleware
     * @return static
     * @deprecated This method is deprecated and will be removed in the near future.
     */
    public function setMiddleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Add middleware class-name
     *
     * @param string $middleware
     * @return static
     */
    public function addMiddleware(string $middleware): RouteInterface
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Set middlewares array
     *
     * @param array $middlewares
     * @return static
     */
    public function setMiddlewares(array $middlewares): RouteInterface
    {
        $this->middlewares = $middlewares;

        return $this;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Set default regular expression used when matching parameters.
     * This is used when no custom parameter regex is found.
     *
     * @param string $regex
     * @return static
     */
    public function setDefaultParameterRegex(string $regex): self
    {
        $this->defaultParameterRegex = $regex;

        return $this;
    }

    /**
     * Get default regular expression used when matching parameters.
     *
     * @return string
     */
    public function getDefaultParameterRegex(): string
    {
        return $this->defaultParameterRegex;
    }
}
