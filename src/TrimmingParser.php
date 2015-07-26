<?php

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
     * @param Parser $delegate
     * @param Parser $left
     * @param Parser $right
     */
    public function __construct(Parser $delegate, Parser $left, Parser $right)
    {
        parent::__construct($delegate);

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
        } while ($current->isSuccess());

        $result = $this->_delegate->parseOn($current);

        if ($result->isFailure()) {
            return $result;
        }

        $current = $result;

        do {
            $current = $this->_right->parseOn($current);
        } while ($current->isSuccess());

        return $current->success($result->getValue());
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new TrimmingParser($this->_delegate, $this->_left, $this->_right);
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
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
