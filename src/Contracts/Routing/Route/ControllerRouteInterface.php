<?php

namespace ApiFramework\Contracts\Routing\Route;

interface ControllerRouteInterface extends LoadableRouteInterface
{
    /**
     * Get controller class-name
     *
     * @return string
     */
    public function getController(): string;
}
