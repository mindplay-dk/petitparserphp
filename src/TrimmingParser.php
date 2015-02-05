<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * A parser that silently consumes input of another parser around
 * its delegate.
 */
class TrimmingParser extends DelegateParser
{
    /**
     * @var Parser
     */
    protected $_left;

    /**
     * @var Parser
     */
    protected $_right;

    /**
     * @param Parser $parser
     * @param Parser $trimmer
     */
    public function __construct(Parser $parser, Parser $left, Parser $right)
    {
        parent::__construct($parser);

        $this->_left = $left;
        $this->_right = $right;
    }

    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        $current = $context;

        do {
            $current = $this->_left->parseOn($current);
        } while ($current->isSuccess);

        $result = $this->_delegate->parseOn($current);

        if ($result->isFailure) {
            return $result;
        }

        $current = $result;

        do {
            $current = $this->_right->parseOn($current);
        } while ($current->isSuccess);

        return $current->success($result->value);
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new TrimmingParser($this->_delegate, $this->_left, $this->_right);
    }

    /**
     * @return Parser[]
     * @see $children
     */
    protected function get_children()
    {
        return array($this->_delegate, $this->_left, $this->_right);
    }

    /**
     * @param Parser $source
     * @param Parser $target
     */
    public function replace(Parser $source, Parser $target)
    {
        parent::replace($source, $target);

        if ($this->_left === $source) {
            $this->_left = $target;
        }

        if ($this->_right === $source) {
            $this->_right = $target;
        }
    }
}
