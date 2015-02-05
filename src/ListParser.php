<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * Abstract parser that parses a list of things in some way.
 *
 * @property-read Parser[] $children
 */
abstract class ListParser extends Parser
{
    /**
     * @var Parser[]
     */
    protected $_parsers;

    /**
     * @param Parser[] $parsers
     */
    public function __construct($parsers)
    {
        $this->_parsers = $parsers;
    }

    /**
     * @see $children
     */
    protected function get_children()
    {
        return $this->_parsers;
    }

    /**
     * @inheritdoc
     */
    public function replace(Parser $source, Parser $target)
    {
        parent::replace($source, $target);

        for ($i=0; $i < count($this->_parsers); $i++) {
            if ($this->_parsers[$i] == $source) {
                $this->_parsers[$i] = $target;
            }
        }
    }
}
