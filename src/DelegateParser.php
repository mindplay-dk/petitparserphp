<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * A parser that delegates to another one. Normally users do not need to
 * directly use a delegate parser.
 */
class DelegateParser extends Parser
{
    /**
     * @var Parser
     */
    protected $_delegate;

    /**
     * @param Parser $delegate
     */
    public function __construct(Parser $delegate)
    {
        $this->_delegate = $delegate;
    }

    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        return $this->_delegate->parseOn($context);
    }

    /**
     * @return Parser[]
     */
    protected function get_children()
    {
        return array($this->_delegate);
    }

    /**
     * @param Parser $source
     * @param Parser $target
     */
    public function replace(Parser $source, Parser $target)
    {
        parent::replace($source, $target);

        if ($this->_delegate === $source) {
            $this->_delegate = $target;
        }
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new DelegateParser($this->_delegate);
    }
}
