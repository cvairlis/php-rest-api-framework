<?php

namespace ApiFramework\Http;

use ApiFramework\Contracts\Routing\Route\LoadableRouteInterface;
use ApiFramework\Routing\Route\RouteUrl;
use ApiFramework\Routing\Router\BasicRouter;

class Request
{
    public const REQUEST_TYPE_GET = 'get';
    public const REQUEST_TYPE_POST = 'post';
    public const REQUEST_TYPE_PUT = 'put';
    public const REQUEST_TYPE_PATCH = 'patch';
    public const REQUEST_TYPE_OPTIONS = 'options';
    public const REQUEST_TYPE_DELETE = 'delete';
    public const REQUEST_TYPE_HEAD = 'head';

    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_FORM_DATA = 'multipart/form-data';
    public const CONTENT_TYPE_X_FORM_ENCODED = 'application/x-www-form-urlencoded';

    public const FORCE_METHOD_KEY = '_method';

    /**
     * All request-types
     * @var string[]
     */
    public static $requestTypes = [
        self::REQUEST_TYPE_GET,
        self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH,
        self::REQUEST_TYPE_OPTIONS,
        self::REQUEST_TYPE_DELETE,
        self::REQUEST_TYPE_HEAD,
    ];

    /**
     * Post request-types.
     * @var string[]
     */
    public static $requestTypesPost = [
        self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH,
        self::REQUEST_TYPE_DELETE,
    ];

    /**
     * Additional data
     *
     * @var array
     */
    private $data = [];

    /**
     * Server headers
     * @var array
     */
    protected $headers = [];

    /**
     * Request ContentType
     * @var string
     */
    protected $contentType;

    /**
     * Request host
     * @var string
     */
    protected $host;

    /**
     * Request body
     *
     * @var array|null
     */
    protected $body;

    /**
     * Current request url
     * @var Url
     */
    protected $url;

    /**
     * Request method
     * @var string
     */
    protected $method;

    /**
     * Defines if request has pending rewrite
     * @var bool
     */
    protected $hasPendingRewrite = false;

    /**
     * @var LoadableRouteInterface|null
     */
    protected $rewriteRoute;

    /**
     * Rewrite url
     * @var string|null
     */
    protected $rewriteUrl;

    /**
     * @var array
     */
    protected $loadedRoutes = [];

    /**
     * Request constructor.
     */
    public function __construct()
    {
        foreach ($_SERVER as $key => $value) {
            $this->headers[strtolower($key)] = $value;
            $this->headers[str_replace('_', '-', strtolower($key))] = $value;
        }
        $this->setHost($this->getHeader('http-host'));
        $this->setBody($this->getBody());
        $this->setUrl(new Url($this->getFirstHeader(['unencoded-url', 'request-uri'])));
        $this->setContentType((string)$this->getHeader('content-type'));
        $this->setMethod((string)($_POST[static::FORCE_METHOD_KEY] ?? $this->getHeader('request-method')));
    }

    /**
     * @return Url
     */
    public function getUrl(): Url
    {
        return $this->url;
    }

    /**
     * Copy url object
     *
     * @return Url
     */
    public function getUrlCopy(): Url
    {
        return clone $this->url;
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get http basic auth user
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->getHeader('php-auth-user');
    }

    /**
     * Get http basic auth password
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->getHeader('php-auth-pw');
    }

    /**
     * Get all headers
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get id address
     * If $safe is false, this function will detect Proxys. But the user can edit this header to whatever he wants!
     * https://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php#comment-25086804
     * @param bool $safeMode When enabled, only safe non-spoofable headers will be returned. Note this can cause issues when using proxy.
     * @return string|null
     */
    public function getIp(bool $safeMode = false): ?string
    {
        $headers = ['remote-addr'];
        if ($safeMode === false) {
            $headers = array_merge($headers, [
                'http-cf-connecting-ip',
                'http-client-ip',
                'http-x-forwarded-for',
            ]);
        }

        return $this->getFirstHeader($headers);
    }

    /**
     * Get header value by name
     *
     * @param string $name Name of the header.
     * @param string|mixed|null $defaultValue Value to be returned if header is not found.
     * @param bool $tryParse When enabled the method will try to find the header from both from client (http) and server-side variants, if the header is not found.
     *
     * @return string|null
     */
    public function getHeader(string $name, $defaultValue = null, bool $tryParse = true): ?string
    {
        $name = strtolower($name);
        $header = $this->headers[$name] ?? null;

        if ($tryParse === true && $header === null) {
            if (strpos($name, 'http-') === 0) {
                // Trying to find client header variant which was not found, searching for header variant without http- prefix.
                $header = $this->headers[str_replace('http-', '', $name)] ?? null;
            } else {
                // Trying to find server variant which was not found, searching for client variant with http- prefix.
                $header = $this->headers['http-' . $name] ?? null;
            }
        }

        return $header ?? $defaultValue;
    }

    /**
     * Will try to find first header from list of headers.
     *
     * @param array $headers
     * @param mixed|null $defaultValue
     * @return mixed|null
     */
    public function getFirstHeader(array $headers, $defaultValue = null)
    {
        foreach ($headers as $header) {
            $header = $this->getHeader($header);
            if ($header !== null) {
                return $header;
            }
        }

        return $defaultValue;
    }

    /**
     * Get request content-type
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * Set request content-type
     * @param string $contentType
     * @return $this
     */
    protected function setContentType(string $contentType): self
    {
        if (strpos($contentType, ';') > 0) {
            $this->contentType = strtolower(substr($contentType, 0, strpos($contentType, ';')));
        } else {
            $this->contentType = strtolower($contentType);
        }

        return $this;
    }

    /**
     * @param Url $url
     */
    public function setUrl(Url $url): void
    {
        $this->url = $url;

        if ($this->url->getHost() === null) {
            $this->url->setHost((string)$this->getHost());
        }
    }

    /**
     * @param string|null $host
     */
    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    /**
     * @param array $body
     * @return void
     */
    public function setBody(?array $body): void
    {
        $this->body = $body;
    }

    /**
     * @return array
     */
    public function getBody(): ?array
    {
        return json_decode(file_get_contents('php://input'), true);
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = strtolower($method);
    }

    /**
     * Get rewrite route
     *
     * @return LoadableRouteInterface|null
     */
    public function getRewriteRoute(): ?LoadableRouteInterface
    {
        return $this->rewriteRoute;
    }

    /**
     * Get rewrite url
     *
     * @return string|null
     */
    public function getRewriteUrl(): ?string
    {
        return $this->rewriteUrl;
    }

    /**
     * Set rewrite url
     *
     * @param string $rewriteUrl
     * @return static
     */
    public function setRewriteUrl(string $rewriteUrl): self
    {
        $this->hasPendingRewrite = true;
        $this->rewriteUrl = rtrim($rewriteUrl, '/') . '/';

        return $this;
    }

    /**
     * Get loaded route
     * @return LoadableRouteInterface|null
     */
    public function getLoadedRoute(): ?LoadableRouteInterface
    {
        return (count($this->loadedRoutes) > 0) ? end($this->loadedRoutes) : null;
    }

    /**
     * Get all loaded routes
     *
     * @return array
     */
    public function getLoadedRoutes(): array
    {
        return $this->loadedRoutes;
    }

    /**
     * Set loaded routes
     *
     * @param array $routes
     * @return static
     */
    public function setLoadedRoutes(array $routes): self
    {
        $this->loadedRoutes = $routes;
        return $this;
    }

    /**
     * Added loaded route
     *
     * @param LoadableRouteInterface $route
     * @return static
     */
    public function addLoadedRoute(LoadableRouteInterface $route): self
    {
        $this->loadedRoutes[] = $route;
        return $this;
    }

    /**
     * Returns true if the request contains a rewrite
     *
     * @return bool
     */
    public function hasPendingRewrite(): bool
    {
        return $this->hasPendingRewrite;
    }

    /**
     * Set rewrite callback
     * @param string|\Closure $callback
     * @return static
     */
    public function setRewriteCallback($callback): self
    {
        $this->hasPendingRewrite = true;

        return $this->setRewriteRoute(new RouteUrl($this->getUrl()->getPath(), $callback));
    }

    /**
     * Set rewrite route
     *
     * @param LoadableRouteInterface $route
     * @return static
     */
    public function setRewriteRoute(LoadableRouteInterface $route): self
    {
        $this->hasPendingRewrite = true;
        $this->rewriteRoute = $route;

        return $this;
    }

    /**
     * Defines if the current request contains a rewrite.
     *
     * @param bool $boolean
     * @return Request
     */
    public function setHasPendingRewrite(bool $boolean): self
    {
        $this->hasPendingRewrite = $boolean;
        return $this;
    }

    public function __isset($name): bool
    {
        return array_key_exists($name, $this->data) === true;
    }

    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }
}
