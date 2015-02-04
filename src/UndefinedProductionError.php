<?php

namespace petitparser;

/**
 * Error raised when an undefined production is accessed.
 */
class UndefinedProductionError extends Error
{
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->message = "Undefined production: {$name}";
    }
}
