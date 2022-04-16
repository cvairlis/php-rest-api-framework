<?php

namespace ApiFramework\Http;

use JsonSerializable;

class Response
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Set the http status code
     *
     * @param int $code
     * @return static
     */
    public function httpCode(int $code): self
    {
        http_response_code($code);

        return $this;
    }

    /**
     * Redirect the response
     *
     * @param string $url
     * @param ?int $httpCode
     */
    public function redirect(string $url, ?int $httpCode = null): void
    {
        if ($httpCode !== null) {
            $this->httpCode($httpCode);
        }

        $this->header('location: ' . $url);
        exit(0);
    }

    /**
     * Json encode
     * @param array|JsonSerializable $value
     * @param ?int $options JSON options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT, JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR.
     * @param int $dept JSON debt.
     */
    public function json($value, ?int $options = null, int $dept = 512): void
    {
        $this->header('Content-Type: application/json; charset=utf-8');
        echo json_encode($value, $options, $dept);
        exit(0);
    }

    /**
     * Add header to response
     * @param string $value
     * @return static
     */
    public function header(string $value): self
    {
        header($value);

        return $this;
    }

    /**
     * Add multiple headers to response
     * @param array $headers
     * @return static
     */
    public function headers(array $headers): self
    {
        foreach ($headers as $header) {
            $this->header($header);
        }

        return $this;
    }
}
