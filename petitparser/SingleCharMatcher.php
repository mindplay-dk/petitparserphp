<?php

namespace petitparser;

use InvalidArgumentException;

class SingleCharMatcher extends CharMatcher
{
    /**
     * @var string
     */
    private $_value;

    /**
     * @param int $value
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($value)
    {
        if (mb_strlen($value) !== 1) {
            throw new InvalidArgumentException("expected single character, got " . mb_strlen($value));
        }

        $this->_value = strlen($value) === 1 ? ord($value) : $value;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function match($value)
    {
        return $this->_value === $value;
    }
}
