<?php

namespace petitparser;

/**
 * Models a group of operators of the same precedence.
 */
class ExpressionGroup
{
    /**
     * @var Parser[]
     */
    protected $_primitives = array();

    /**
     * @var Parser[]
     */
    protected $_prefix = array();

    /**
     * @var Parser[]
     */
    protected $_postfix = array();

    /**
     * @var Parser[]
     */
    protected $_right = array();

    /**
     * @var Parser[]
     */
    protected $_left = array();

    /**
     * Defines a new primitive or literal [parser].
     *
     * @param Parser $parser
     *
     * @return void
     */
    public function primitive(Parser $parser)
    {
        $this->_primitives[] = $parser;
    }

    /**
     * @param Parser $inner
     *
     * @return Parser
     *
     * @ignore
     */
    public function _build_primitive(Parser $inner)
    {
        return $this->_build_choice($this->_primitives, $inner);
    }

    /**
     * Adds a prefix operator [parser]. Evaluates the optional [action] with the
     * parsed `operator` and `value`.
     *
     * @param Parser   $parser
     * @param callable $action optional closure of the form: mixed function($operator, $value)
     *
     * @return void
     */
    public function prefix(Parser $parser, $action = null)
    {
        if ($action === null) {
            $action = function ($operator, $value) {
                return array($operator, $value);
            };
        }

        $this->_prefix[] = $parser->map(
            function ($operator) use ($action) {
                return new ExpressionResult($operator, $action);
            }
        );
    }

    /**
     * @param Parser $inner
     *
     * @return Parser
     *
     * @ignore
     */
    public function _build_prefix(Parser $inner)
    {
        if (count($this->_prefix) === 0) {
            return $inner;
        } else {
            $parser = new SequenceParser(array(
                $this->_build_choice($this->_prefix)->star(),
                $inner,
            ));

            return $parser->map(
                function ($tuple) {
                    /**
                     * @var ExpressionResult[] $results
                     */
                    $results = array_reverse($tuple[0]);

                    $value = $tuple[count($tuple) - 1];

                    foreach ($results as $result) {
                        $value = call_user_func($result->action, $result->operator, $value);
                    }

                    return $value;
                }
            );
        }
    }

    /**
     * Adds a postfix operator [parser]. Evaluates the optional [action] with the
     * parsed `value` and `operator`.
     *
     * @param Parser   $parser
     * @param callable $action closure of the form: mixed function($value, $operator)
     *
     * @return void
     */
    public function postfix(Parser $parser, $action = null)
    {
        if ($action == null) {
            $action = function ($value, $operator) {
                return array($value, $operator);
            };
        }

        $this->_postfix[] = $parser->map(
            function ($operator) use ($action) {
                return new ExpressionResult($operator, $action);
            }
        );
    }

    /**
     * @param Parser $inner
     *
     * @return Parser
     */
    public function _build_postfix(Parser $inner)
    {
        if (count($this->_postfix) === 0) {
            return $inner;
        } else {
            $parser = new SequenceParser(array(
                $inner,
                $this->_build_choice($this->_postfix)->star(),
            ));

            return $parser->map(
                function ($tuple) {
                    /**
                     * @var ExpressionResult[] $results
                     */
                    $results = array_reverse($tuple[count($tuple) - 1]);

                    $value = $tuple[0];

                    foreach ($results as $result) {
                        $value = call_user_func($result->action, $value, $result->operator);
                    }

                    return $value;
                }
            );
        }
    }

    /**
     * Adds a right-associative operator [parser]. Evaluates the optional [action] with
     * the parsed `left` term, `operator`, and `right` term.
     *
     * @param Parser   $parser
     * @param callable $action closure of the form: mixed function($left, $operator, $right)
     *
     * @return void
     */
    public function right(Parser $parser, $action = null)
    {
        if ($action === null) {
            $action = function ($left, $operator, $right) {
                return array($left, $operator, $right);
            };
        }

        $this->_right[] = $parser->map(
            function ($operator) use ($action) {
                return new ExpressionResult($operator, $action);
            }
        );
    }

    /**
     * @param Parser $inner
     *
     * @return Parser
     */
    public function _build_right(Parser $inner)
    {
        if (count($this->_right) === 0) {
            return $inner;
        } else {
            return $inner
                ->separatedBy($this->_build_choice($this->_right))
                ->map(
                    function ($sequence) {
                        /**
                         * @var ExpressionResult $result
                         * TODO type hints don't seem to work for the $sequence argument
                         */

                        $result = $sequence[count($sequence) - 1];

                        for ($i = count($sequence) - 2; $i > 0; $i -= 2) {
                            $result = call_user_func($sequence[$i]->action, $sequence[$i - 1], $sequence[$i]->operator, $result);
                        }

                        return $result;
                    }
                );
        }
    }

    /**
     * Adds a left-associative operator [parser]. Evaluates the optional [action] with
     * the parsed `left` term, `operator`, and `right` term.
     *
     * @param Parser   $parser
     * @param callable $action closure of the form: mixed function($left, $operator, $right)
     *
     * @return void
     */
    public function left(Parser $parser, $action = null)
    {
        if ($action === null) {
            $action = function ($left, $operator, $right) {
                return array($left, $operator, $right);
            };
        }

        $this->_left[] = $parser->map(
            function ($operator) use ($action) {
                return new ExpressionResult($operator, $action);
            }
        );
    }

    /**
     * @param Parser $inner
     *
     * @return Parser
     */
    public function  _build_left(Parser $inner)
    {
        if (count($this->_left) === 0) {
            return $inner;
        } else {
            return $inner
                ->separatedBy($this->_build_choice($this->_left))
                ->map(
                    function ($sequence) {
                        // TODO fix type-hints
                        $result = $sequence[0];

                        for ($i = 1; $i < count($sequence); $i += 2) {
                            $result = call_user_func(
                                $sequence[$i]->action,
                                $result,
                                $sequence[$i]->operator,
                                $sequence[$i + 1]
                            );
                        }

                        return $result;
                    }
                );
        }
    }

    /**
     * helper to build an optimal choice parser
     *
     * @param Parser[] $parsers
     * @param Parser   $otherwise
     *
     * @return Parser
     */
    public function _build_choice($parsers, Parser $otherwise = null)
    {
        if (count($parsers) === 0) {
            return $otherwise;
        } elseif (count($parsers) === 1) {
            return $parsers[0];
        } else {
            return new ChoiceParser($parsers);
        }
    }

    /**
     * helper to build the group of parsers
     *
     * @param Parser $inner
     *
     * @return Parser
     *
     * @ignore
     */
    public function _build(Parser $inner)
    {
        return $this->_build_left(
            $this->_build_right(
                $this->_build_postfix(
                    $this->_build_prefix(
                        $this->_build_primitive($inner)
                    )
                )
            )
        );
    }
}
