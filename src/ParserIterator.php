<?php

namespace petitparser;

use Iterator;

class ParserIterator implements Iterator
{
    /**
     * @var Parser
     */
    protected $_root;

    /**
     * @var Parser[]
     */
    protected $_todo;

    /**
     * @var Parser[]
     */
    protected $_done;

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
     * @param Parser $root
     */
    public function __construct(Parser $root)
    {
        $this->_root = $root;
    }

    /**
     * @param Parser $root
     */
    protected function _init(Parser $root)
    {
        $this->_todo = array($root);
        $this->_done = array();
        $this->_current = null;
        $this->_index = 0;
        $this->_valid = $this->_moveNext();
    }

    /**
     * @return bool
     */
    protected function _moveNext()
    {
        do {
            if (count($this->_todo) === 0) {
                $this->_current = null;
                return false;
            }

            $this->_current = array_pop($this->_todo);
        } while (in_array($this->_current, $this->_done, true));

        $this->_done[] = $this->_current;

        foreach ($this->_current->children as $child) {
            $this->_todo[] = $child;
        }

        return true;
    }

    /**
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void
     */
    public function rewind()
    {
        $this->_init($this->_root);
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
