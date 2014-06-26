<?php

namespace petitparser;

class RangeCharMatcher extends CharMatcher
{
    /**
     * @var int|string
     */
    protected $_start;

    /**
     * @var int|string
     */
    protected $_stop;

    /**
     * @param int $start
     * @param int $stop
     */
    public function __construct($start, $stop)
    {
        $this->_start = strlen($start) === 1 ? ord($start) : $start;
        $this->_stop = strlen($stop) === 1 ? ord($stop) : $stop;
    }

    /**
     * @param int|string $value
     *
     * @return bool
     */
    public function match($value)
    {
        if (is_int($value)) {
            return $value >= $this->_start
                && $value <= $this->_stop;
        } else {
            return false; // TODO implement this
        }
    }
}
