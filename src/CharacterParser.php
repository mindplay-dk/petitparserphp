<?php

namespace petitparser;

/**
 * Parser class for individual character classes.
 *
 * TODO update to latest version; udpate character matchers (now called "predicates")
 */
class CharacterParser extends Parser
{
    /**
     * @var CharMatcher
     */
    private $_matcher;

    /**
     * @var string
     */
    private $_message;

    /**
     * @param CharMatcher $matcher
     * @param string       $message
     */
    public function __construct(CharMatcher $matcher, $message)
    {
        $this->_matcher = $matcher;
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

        if ($position < $buffer->length && $this->_matcher->match($buffer->charCodeAt($position))) {
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
        return new CharacterParser($this->_matcher, $this->_message);
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
            && $this->_matcher === $other->_matcher
            && $this->_message === $other->_message;
    }
}
