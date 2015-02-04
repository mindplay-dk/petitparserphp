<?php

namespace petitparser;

use InvalidArgumentException;

class SingleCharMatcher extends CharMatcher
{
    /**
     * @var int
     */
    private $_value;

    /**
     * @param int|string $value
     */
    public function __construct($value)
    {
        $this->_value = toCharCode($value);
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    public function match($value)
    {
        return $this->_value === $value;
    }
}
