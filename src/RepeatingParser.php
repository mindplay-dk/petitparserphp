<?php

namespace petitparser;

/**
 * A parser that repeatedly parses a sequence of parsers.
 */
abstract class RepeatingParser extends DelegateParser
{
    /**
     * @var int
     */
    protected $_min;

    /**
     * @var int
     */
    protected $_max;

    /**
     * @param Parser $parser
     * @param int $min
     * @param int $max
     */
    public function __construct(Parser $parser, $min, $max)
    {
        parent::__construct($parser);

        $this->_min = $min;
        $this->_max = $max;
    }

    public function __toString()
    {
        return parent::__toString() . '[' . $this->_min . '..' . $this->_max . ']';
    }

    public function hasEqualProperties(Parser $other)
    {
        return parent::hasEqualProperties($other)
            && $other instanceof self
            && $this->_min === $other->_min
            && $this->_max === $other->_max;
    }
}
