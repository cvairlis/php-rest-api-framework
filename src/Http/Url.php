<?php

namespace ApiFramework\Http;

class Url
{
    /**
     * @var string|null
     */
    private $host;

    /**
     * @var string|null
     */
    private $path;

    /**
     * @var array
     */
    private $params = [];

    /**
     * Url constructor.
     *
     * @param ?string $url
     */
    public function __construct(?string $url)
    {
        if ($url !== null && $url !== '/') {
            $data = $this->parseUrl($url);
            $this->host = $data['host'] ?? null;

            if (isset($data['path']) === true) {
                $this->setPath($data['path']);
            }

            if (isset($data['query']) === true) {
                $this->setQueryString($data['query']);
            }
        }
    }

    /**
     * Get url host
     *
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * Set the host of the url
     *
     * @param string $host
     * @return static
     */
    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get path from url
     * @return string
     */
    public function getPath(): ?string
    {
        return $this->path ?? '/';
    }

    /**
     * Set the url path
     *
     * @param string $path
     * @return static
     */
    public function setPath(string $path): self
    {
        $this->path = rtrim($path, '/') . '/';

        return $this;
    }

    /**
     * Get query-string from url
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set the url params
     *
     * @param array $params
     * @return static
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Set raw query-string parameters as string
     *
     * @param string $queryString
     * @return static
     */
    public function setQueryString(string $queryString): self
    {
        $params = [];
        parse_str($queryString, $params);

        if (count($params) > 0) {
            return $this->setParams($params);
        }

        return $this;
    }

    /**
     * Get query-string params as string
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return static::arrayToParams($this->getParams());
    }

    /**
     * Get parameter by name.
     * Returns parameter value or default value.
     *
     * @param string $name
     * @param string|null $defaultValue
     * @return string|null
     */
    public function getParam(string $name, ?string $defaultValue = null): ?string
    {
        return (isset($this->getParams()[$name]) === true) ? $this->getParams()[$name] : $defaultValue;
    }

    /**
     * UTF-8 aware parse_url() replacement.
     * @param string $url
     * @param int $component
     * @return array
     */
    public function parseUrl(string $url, int $component = -1): array
    {
        $encodedUrl = preg_replace_callback(
            '/[^:\/@?&=#]+/u',
            static function ($matches): string {
                return urlencode($matches[0]);
            },
            $url
        );

        $parts = parse_url($encodedUrl, $component);

        return array_map('urldecode', $parts);
    }

    /**
     * Convert array to query-string params
     *
     * @param array $getParams
     * @param bool $includeEmpty
     * @return string
     */
    public static function arrayToParams(array $getParams = [], bool $includeEmpty = true): string
    {
        if (count($getParams) !== 0) {
            if ($includeEmpty === false) {
                $getParams = array_filter($getParams, static function ($item): bool {
                    return (trim($item) !== '');
                });
            }

            return http_build_query($getParams);
        }

        return '';
    }
}
