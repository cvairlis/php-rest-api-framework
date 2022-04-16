<?php

namespace ApiFramework\Routing\Route;

use ApiFramework\Contracts\Routing\Route\LoadableRouteInterface;
use ApiFramework\Contracts\Routing\Route\RouteInterface;
use ApiFramework\Http\Request;
use ApiFramework\Routing\Router\Router;

abstract class LoadableRoute extends Route implements LoadableRouteInterface
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $regex;

    /**
     * Loads and renders middlewares-classes
     *
     * @param Request $request
     * @param Router $router
     */
    public function loadMiddleware(Request $request, Router $router): void
    {
        foreach ($this->getMiddlewares() as $middleware) {
            if (is_object($middleware) === false) {
                $middleware = $router->getFilesystem()->loadClass($middleware);
            }

            $className = get_class($middleware);
            $middleware->handle($request);
        }
    }

    /**
     * Set url
     *
     * @param string $url
     * @return static
     */
    public function setUrl(string $url): LoadableRouteInterface
    {
        $this->url = ($url === '/') ? '/' : '/' . trim($url, '/') . '/';

        if (strpos($this->url, $this->paramModifiers[0]) !== false) {
            $regex = sprintf(static::PARAMETERS_REGEX_FORMAT, $this->paramModifiers[0], $this->paramOptionalSymbol, $this->paramModifiers[1]);

            if ((bool)preg_match_all('/' . $regex . '/u', $this->url, $matches) !== false) {
                $this->parameters = array_fill_keys($matches[1], null);
            }
        }

        return $this;
    }

    /**
     * Prepends url while ensuring that the url has the correct formatting.
     *
     * @param string $url
     * @return LoadableRouteInterface
     */
    public function prependUrl(string $url): LoadableRouteInterface
    {
        return $this->setUrl(rtrim($url, '/') . $this->url);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Find url that matches method, parameters or name.
     * Used when calling the url() helper.
     *
     * @param string|null $method
     * @param string|array|null $parameters
     * @param string|null $name
     * @return string
     */
    public function findUrl(?string $method = null, $parameters = null, ?string $name = null): string
    {
        $url = $this->getUrl();

        /* Create the param string - {parameter} */
        $param1 = $this->paramModifiers[0] . '%s' . $this->paramModifiers[1];

        /* Create the param string with the optional symbol - {parameter?} */
        $param2 = $this->paramModifiers[0] . '%s' . $this->paramOptionalSymbol . $this->paramModifiers[1];

        /* Replace any {parameter} in the url with the correct value */

        $params = $this->getParameters();

        foreach (array_keys($params) as $param) {
            if ($parameters === '' || (is_array($parameters) === true && count($parameters) === 0)) {
                $value = '';
            } else {
                $p = (array)$parameters;
                $value = array_key_exists($param, $p) ? $p[$param] : $params[$param];

                /* If parameter is specifically set to null - use the original-defined value */
                if ($value === null && isset($this->originalParameters[$param]) === true) {
                    $value = $this->originalParameters[$param];
                }
            }

            if (stripos($url, $param1) !== false || stripos($url, $param) !== false) {
                /* Add parameter to the correct position */
                $url = str_ireplace([sprintf($param1, $param), sprintf($param2, $param)], $value, $url);
            } else {
                /* Parameter aren't recognized and will be appended at the end of the url */
                $url .= $value . '/';
            }
        }

        return rtrim('/' . ltrim($url, '/'), '/') . '/';
    }

    /**
     * Returns the provided name for the router.
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Check if route has given name.
     *
     * @param string $name
     * @return bool
     */
    public function hasName(string $name): bool
    {
        return strtolower($this->name) === strtolower($name);
    }

    /**
     * Sets the router name, which makes it easier to obtain the url or router at a later point.
     * Alias for LoadableRoute::setName().
     *
     * @param string|array $name
     * @return static
     * @see LoadableRoute::setName()
     */
    public function name($name): LoadableRouteInterface
    {
        return $this->setName($name);
    }

    /**
     * Sets the router name, which makes it easier to obtain the url or router at a later point.
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): LoadableRouteInterface
    {
        $this->name = $name;

        return $this;
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
        if (isset($settings['as']) === true) {
            $name = $settings['as'];

            if ($this->name !== null && $merge !== false) {
                $name .= '.' . $this->name;
            }

            $this->setName($name);
        }

        if (isset($settings['prefix']) === true) {
            $this->prependUrl($settings['prefix']);
        }

        return parent::setSettings($settings, $merge);
    }
}
