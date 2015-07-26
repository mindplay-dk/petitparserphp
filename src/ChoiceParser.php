<?php

namespace petitparser;

/**
 * A parser that uses the first parser that succeeds.
 */
class ChoiceParser extends ListParser
{
    /**
     * @param Context $context
     *
     * @return Result[]
     */
    public function parseOn(Context $context)
    {
        for ($i=0; $i < count($this->_parsers); $i++) {
            $result = $this->_parsers[$i]->parseOn($context);

            if ($result->isSuccess()) {
                return $result;
            }
        }

        /** @noinspection PhpUndefinedVariableInspection */
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function or_(Parser $other)
    {
        $parsers = $this->_parsers;
        $parsers[] = $other;

        return new ChoiceParser($parsers);
    }

    /**
     * @return Parser
     */
    function copy() {
        return new ChoiceParser($this->_parsers);
    }
}
