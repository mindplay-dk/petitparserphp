<?php

namespace petitparser;

use IteratorAggregate;

class ParserIterable implements IteratorAggregate
{
    /**
     * @var Parser
     */
    protected $_root;

    /**
     * @param Parser $root
     */
    public function __construct(Parser $root)
    {
        $this->_root = $root;
    }

    /**
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     */
    public function getIterator()
    {
        return new ParserIterator(array($this->_root));
    }
}
