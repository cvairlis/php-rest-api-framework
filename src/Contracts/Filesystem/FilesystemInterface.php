<?php

namespace ApiFramework\Contracts\Filesystem;

interface FilesystemInterface
{
    /**
     * Called when loading class
     * @param string $class
     * @return object
     */
    public function loadClass(string $class);

    /**
     * Called when loading class method
     * @param object $class
     * @param string $method
     * @param array $parameters
     * @return object
     */
    public function loadClassMethod($class, string $method, array $parameters);

    /**
     * Called when loading method
     *
     * @param callable $closure
     * @param array $parameters
     * @return mixed
     */
    public function loadClosure(callable $closure, array $parameters);
}
