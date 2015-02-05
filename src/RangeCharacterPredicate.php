<?php

declare(encoding='UTF-8');

namespace petitparser;

class RangeCharacterPredicate extends CharacterPredicate
{
    /**
     * @var int
     */
    public $start;

    /**
     * @var int
     */
    public $stop;

    /**
     * @param int $start
     * @param int $stop
     */
    public function __construct($start, $stop)
    {
        $this->start = toCharCode($start);
        $this->stop = toCharCode($stop);
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    public function test($value)
    {
        return $value >= $this->start
            && $value <= $this->stop;
    }
}
