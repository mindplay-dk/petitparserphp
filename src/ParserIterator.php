<?php

namespace petitparser;

use Iterator;

class ParserIterator implements Iterator
{
    /**
     * @var Parser[]
     */
    protected $_roots;

    /**
     * @var Parser[]
     */
    protected $_todo;

    /**
     * @var Parser[]
     */
    protected $_seen;

    /**
     * @var Parser
     */
    protected $_current;

    /**
     * @var int
     */
    protected $_index;

    /**
     * @var bool
     */
    protected $_valid;

    /**
     * @param Parser[] $roots
     */
    public function __construct(array $roots)
    {
        $this->_roots = $roots;
    }

    /**
     * @param Parser[] $roots
     */
    protected function _init(array $roots)
    {
        $this->_todo = $roots;
        $this->_seen = $roots;
        $this->_current = null;
        $this->_index = 0;
        $this->_valid = $this->_moveNext();
    }

    /**
     * @return bool
     */
    protected function _moveNext()
    {
        if (count($this->_todo) === 0) {
            $this->_current = null;

            return false;
        }

        $this->_current = array_pop($this->_todo);

        foreach ($this->_current->children as $parser) {
            if (! in_array($parser, $this->_seen)) {
                $this->_todo[] = $parser;
                $this->_seen[] = $parser;
            }
        }

        return true;
    }

    /**
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void
     */
    public function rewind()
    {
        $this->_init($this->_roots);
    }

    /**
     * @link http://php.net/manual/en/iterator.next.php
     * @return void
     */
    public function next()
    {
        if ($this->_valid = $this->_moveNext()) {
            $this->_index += 1;
        }
    }

    /**
     * @link http://php.net/manual/en/iterator.valid.php
     * @return bool
     */
    public function valid()
    {
        return $this->_valid;
    }

    /**
     * @link http://php.net/manual/en/iterator.current.php
     */
    public function current()
    {
        return $this->_current;
    }

    /**
     * @link http://php.net/manual/en/iterator.key.php
     * @return int
     */
    public function key()
    {
        return $this->_index;
    }
}
