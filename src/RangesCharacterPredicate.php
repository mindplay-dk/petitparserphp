<?php

namespace petitparser;

class RangesCharacterPredicate extends CharacterPredicate
{
    /**
     * @var int
     */
    public $length;

    /**
     * @var int[]
     */
    public $starts;

    /**
     * @var int[]
     */
    public $stops;

    /**
     * @param int   $length
     * @param int[] $starts
     * @param int[] $stops
     */
    public function __construct($length, $starts, $stops)
    {
        $this->length = $length;
        $this->starts = $starts;
        $this->stops = $stops;
    }

    /**
     * @param int $value
     *
     * @return bool
     */
    public function test($value)
    {
        $min = 0;
        $max = $this->length;

        while ($min < $max) {
            $mid = $min + (($max - $min) >> 1);
            $comp = $this->starts[$mid] - $value;

            if ($comp === 0) {
                return true;
            } elseif ($comp < 0) {
                $min = $mid + 1;
            } else {
                $max = $mid;
            }
        }

        return (0 < $min) && ($value <= $this->stops[$min - 1]);
    }
}
