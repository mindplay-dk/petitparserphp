<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * Error raised when a production is accidentally redefined.
 */
class RedefinedProductionError extends Error
{
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->message = "Redefined production: $name";
    }
}
