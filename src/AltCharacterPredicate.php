<?php

namespace petitparser;

/**
 * Internal character matcher for alternatives.
 */
class AltCharacterPredicate extends CharacterPredicate
{
    /**
     * @var CharacterPredicate[]
     */
    protected $_matchers;

    /**
     * @param CharacterPredicate[] $matchers
     */
    public function __construct($matchers)
    {
        $this->_matchers = $matchers;
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    public function test($value)
    {
        foreach ($this->_matchers as $matcher) {
            if ($matcher->test($value)) {
                return true;
            }
        }

        return false;
    }
}
