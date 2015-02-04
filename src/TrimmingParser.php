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
    protected $_trimmer;

    /**
     * @param Parser $parser
     * @param Parser $trimmer
     */
    public function __construct(Parser $parser, Parser $trimmer)
    {
        parent::__construct($parser);

        $this->_trimmer = $trimmer;
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
            $current = $this->_trimmer->parseOn($current);
        } while ($current->isSuccess);

        $result = $this->_delegate->parseOn($current);

        if ($result->isFailure) {
            return $result;
        }

        $current = $result;

        do {
            $current = $this->_trimmer->parseOn($current);
        } while ($current->isSuccess);

        return $current->success($result->value);
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new TrimmingParser($this->_delegate, $this->_trimmer);
    }

    /**
     * @return Parser[]
     * @see $children
     */
    protected function get_children()
    {
        return array($this->_delegate, $this->_trimmer);
    }

    /**
     * @param Parser $source
     * @param Parser $target
     */
    public function replace(Parser $source, Parser $target)
    {
        parent::replace($source, $target);

        if ($this->_trimmer === $source) {
            $this->_trimmer = $target;
        }
    }
}
