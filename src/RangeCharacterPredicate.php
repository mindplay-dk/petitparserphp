<?php

namespace petitparser;

class RangeCharacterPredicate extends CharacterPredicate
{
    /**
     * @var int
     */
    protected $_start;

    /**
     * @var int
     */
    protected $_stop;

    /**
     * @param int $start
     * @param int $stop
     */
    public function __construct($start, $stop)
    {
        $this->_start = toCharCode($start);
        $this->_stop = toCharCode($stop);
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    public function test($value)
    {
        return $value >= $this->_start
            && $value <= $this->_stop;
    }
}
