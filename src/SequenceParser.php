<?php

declare(encoding='UTF-8');

namespace petitparser;

/**
 * A parser that parses a sequence of parsers.
 */
class SequenceParser extends ListParser
{
    /**
     * @param Context $context
     *
     * @return Result
     */
    public function parseOn(Context $context)
    {
        $current = $context;
        $elements = array();

        for ($i=0; $i < count($this->_parsers); $i++) {
            $result = $this->_parsers[$i]->parseOn($current);

            if ($result->isFailure) {
                return $result;
            }

            $elements[$i] = $result->value;
            $current = $result;
        }

        return $current->success($elements);
    }

    /**
     * @param Parser $other
     *
     * @return Parser
     */
    public function seq(Parser $other)
    {
        $parsers = $this->_parsers;
        $parsers[] = $other;

        return new SequenceParser($parsers);
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new SequenceParser($this->_parsers);
    }
}
