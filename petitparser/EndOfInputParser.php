<?php

namespace petitparser;

/**
 * A parser that succeeds only at the end of the input.
 */
class EndOfInputParser extends DelegateParser
{
    /**
     * @var string
     */
    protected $_message;

    /**
     * @param Parser $parser
     * @param string $message
     */
    public function __construct(Parser $parser, $message)
    {
        parent::__construct($parser);

        $this->_message = $message;
    }

    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        $result = $this->_delegate->parseOn($context);

        if ($result->isFailure || $result->position == length($result->buffer)) {
            return $result;
        }

        return $result->failure($this->_message, $result->position);
    }

    public function __toString()
    {
        return parent::__toString() . '[' . $this->_message . ']';
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new EndOfInputParser($this->_delegate, $this->_message);
    }

    /**
     * @param Parser $other
     *
     * @return bool
     */
    public function equalProperties(Parser $other)
    {
        return parent::equalProperties($other)
            && $other instanceof self
            && $this->_message === $other->_message;
    }
}
