<?php

namespace petitparser;

/**
 * Internal character matcher for alternatives.
 */
class AltCharMatcher extends CharMatcher
{
    /**
     * @var CharMatcher[]
     */
    protected $_matchers;

    /**
     * @param CharMatcher[] $matchers
     */
    public function __construct($matchers)
    {
        $this->_matchers = $matchers;
    }

    /**
     * @param int|string $value
     *
     * @return bool
     */
    public function match($value)
    {
        foreach ($this->_matchers as $matcher) {
            if ($matcher->match($value)) {
                return true;
            }
        }

        return false;
    }
}
