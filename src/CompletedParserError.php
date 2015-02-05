<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * Error raised when somebody tries to modify a [CompositeParser] outside
 * the [CompositeParser.initialize] method.
 */
class CompletedParserError extends Error
{
    public function __construct()
    {
        $this->message = 'Unable to modify completed CompositeParser';
    }
}
