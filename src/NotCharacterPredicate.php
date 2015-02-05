<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * Internal character matcher that negates the result.
 */
class NotCharacterPredicate extends CharacterPredicate
{
    /**
     * @var CharacterPredicate
     */
    protected $_matcher;

    public function __construct(CharacterPredicate $matcher)
    {
        $this->_matcher = $matcher;
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    public function test($value)
    {
        return ! $this->_matcher->test($value);
    }
}
