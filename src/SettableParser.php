<?php

namespace petitparser;

class SettableParser extends DelegateParser
{
    /**
     * @param Parser $parser
     *
     * @return Parser given argument (for method-chaining)
     */
    public function set(Parser $parser)
    {
        $this->replace($this->children[0], $parser);

        return $parser;
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new SettableParser($this->_delegate);
    }
}
