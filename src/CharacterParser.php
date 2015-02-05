<?php

namespace petitparser;

/**
 * Parser class for individual character classes.
 */
class CharacterParser extends Parser
{
    /**
     * @var CharacterPredicate
     */
    private $_predicate;

    /**
     * @var string
     */
    private $_message;

    /**
     * @param CharacterPredicate $predicate
     * @param string       $message
     */
    public function __construct(CharacterPredicate $predicate, $message)
    {
        $this->_predicate = $predicate;
        $this->_message = $message;
    }

    /**
     * @inheritdoc
     */
    public function parseOn(Context $context)
    {
        $buffer = $context->buffer;
        $position = $context->position;
        $char = $buffer->charAt($position);

        if ($position < $buffer->length && $this->_predicate->test($buffer->charCodeAt($position))) {
            return $context->success($char, $position + 1);
        }

        return $context->failure($this->_message);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return parent::__toString() . "[{$this->_message}]";
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new CharacterParser($this->_predicate, $this->_message);
    }

    /**
     * @param Parser   $other
     *
     * @return bool
     */
    public function hasEqualProperties(Parser $other)
    {
        return parent::hasEqualProperties($other)
            && $other instanceof self
            && $this->_predicate === $other->_predicate
            && $this->_message === $other->_message;
    }
}
