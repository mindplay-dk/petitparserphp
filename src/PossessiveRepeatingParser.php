<?php

namespace petitparser;

class PossessiveRepeatingParser extends RepeatingParser
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

        while (count($elements) < $this->_min) {
            $result = $this->_delegate->parseOn($current);

            if ($result->isFailure) {
                return $result;
            }

            $elements[] = $result->value;
            $current = $result;
        }

        while ($this->_max === Parser::UNBOUNDED || count($elements) < $this->_max) {
            $result = $this->_delegate->parseOn($current);

            if ($result->isFailure) {
                return $current->success($elements);
            }

            $elements[] = $result->value;
            $current = $result;
        }

        return $current->success($elements);
    }

    /**
     * @return Parser
     */
    public function copy()
    {
        return new PossessiveRepeatingParser($this->_delegate, $this->_min, $this->_max);
    }
}
