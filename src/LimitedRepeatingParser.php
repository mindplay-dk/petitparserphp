<?php

namespace petitparser;

/**
 * An abstract parser that repeatedly parses between 'min' and 'max' instances of
 * its delegate and that requires the input to be completed with a specified parser
 * 'limit'. Subclasses provide repeating behavior as typically seen in regular
 * expression implementations (non-blind).
 */
abstract class LimitedRepeatingParser extends RepeatingParser
{
    /**
     * @var Parser
     */
    protected $_limit;

    /**
     * @param Parser $parser
     * @param Parser $limit
     * @param int $min
     * @param int $max
     */
    public function __construct(Parser $parser, Parser $limit, $min, $max)
    {
        parent::__construct($parser, $min, $max);

        $this->_limit = $limit;
    }

    /**
     * @see $children
     * @ignore
     */
    protected function get_children()
    {
        return array($this->_delegate, $this->_limit);
    }

    /**
     * @param Parser $source
     * @param Parser $target
     */
    public function replace(Parser $source, Parser $target)
    {
        parent::replace($source, $target);

        if ($this->_limit === $source) {
            $this->_limit = $target;
        }
    }
}
