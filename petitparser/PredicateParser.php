<?php

namespace petitparser;

/**
 * A parser for a literal satisfying a predicate.
 */
class PredicateParser extends Parser
{
    /**
     * @var int
     */
    protected $_length;

    /**
     * @var callable
     */
    protected $_predicate;

    /**
     * @var string
     */
    protected $_message;

    /**
     * @param int $length
     * @param callable $predicate
     * @param string $message
     */
    public function __construct($length, $predicate, $message)
    {
        $this->_length = $length;
        $this->_predicate = $predicate;
        $this->_message = $message;
    }

    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        $start = $context->position;
        $stop = $start + $this->_length;

        if ($stop <= length($context->buffer)) {
            $result = is_string($context->buffer)
                ? mb_substr($context->buffer, $start, $stop - $start)
                : array_slice($context->buffer, $start, $stop - $start);

            if (call_user_func($this->_predicate, $result)) {
                return $context->success($result, $stop);
            }
        }

        return $context->failure($this->_message);
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
        return new PredicateParser($this->_length, $this->_predicate, $this->_message);
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
            && $this->_predicate === $other->_predicate
            && $this->_message === $other->_message;
    }
}
