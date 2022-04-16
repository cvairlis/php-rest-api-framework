<?php

namespace ApiFramework\Filesystem\Exceptions;

use Exception;
use Throwable;

class ClassNotFoundHttpException extends Exception
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string|null
     */
    protected $method;

    public function __construct(string $class, ?string $method = null, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Get class name
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get method
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }
}
