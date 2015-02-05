<?php

declare(encoding='UTF-8');

namespace petitparser;

use InvalidArgumentException;

class SingleCharacterPredicate extends CharacterPredicate
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
    public function test($value)
    {
        return $this->_value === $value;
    }
}
