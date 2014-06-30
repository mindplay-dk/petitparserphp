<?php

namespace petitparser;

/**
 * Internal character matcher that negates the result.
 */
class NotCharMatcher extends CharMatcher
{
    /**
     * @var CharMatcher
     */
    protected $_matcher;

    public function __construct(CharMatcher $matcher)
    {
        $this->_matcher = $matcher;
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    public function match($value)
    {
        return ! $this->_matcher->match($value);
    }
}
