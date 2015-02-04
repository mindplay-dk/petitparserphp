<?php

namespace petitparser;

use RuntimeException;
use ArrayAccess;

/**
 * Helper to compose complex grammars from various primitive parsers.
 *
 * To create a new composite grammar subclass [CompositeParser]. Override
 * the method [initialize] and for every production call [def] giving the
 * production a name. The start production must be named 'start'. To refer
 * to other produtions (forward and backward) use [ref].
 *
 * Consider the following example to parse a list of numbers:
 *
 *     class NumberListGrammar extends CompositeParser {
 *       void initialize() {
 *         def('start', ref('list').end());
 *         def('list', ref('element').separatedBy(char(','),
 *           includeSeparators: false));
 *         def('element', digit().plus().flatten());
 *       }
 *     }
 *
 * You might want to create future subclasses of your composite grammar
 * to redefine the grammar or attach custom actions. In such a subclass
 * override the method [initialize] again and call super. Then use
 * [redef] to redefine an existing production, and [action] to attach an
 * action to an existing production.
 *
 * Consider the following example that attaches a production action and
 * converts the digits to actual numbers:
 *
 *     class NumberListParser extends NumberListGrammar {
 *       void initialize() {
 *         action('element', (value) => int.parse(value));
 *       }
 *     }
 */
abstract class CompositeParser extends DelegateParser implements ArrayAccess
{
    /**
     * @var bool
     */
    protected $_completed = false;

    /**
     * @var Parser[]
     */
    protected $_defined = array();

    /**
     * @var SetableParser[]
     */
    protected $_undefined = array();

    public function __construct()
    {
        parent::__construct(failure('Uninitalized production: start'));

        $this->initialize();
        $this->_complete();
    }

    /**
     * Initializes the composite grammar.
     *
     * @return void
     */
    abstract protected function initialize();

    /**
     * Internal method to complete the grammar.
     *
     * @throws UndefinedProductionError
     * @return void
     */
    protected function _complete()
    {
        $this->_delegate = $this->ref('start');

        foreach ($this->_undefined as $name => $parser) {
            if (! isset($this->_defined[$name])) {
                throw new UndefinedProductionError($name);
            }
            $parser->set($this->_defined[$name]);
        }

        $this->_undefined = array();
        $this->_completed = true;
        $this->_delegate = removeSetables($this->ref('start'));
    }

    /**
     * Returns a reference to a production with a [name].
     *
     * This method works during initialization and after completion of the
     * initialization. During the initialization it returns delegate parsers
     * that are eventually replaced by the real parsers. Afterwards it
     * returns the defined parser (mostly useful for testing).
     *
     * @param string $name
     *
     * @return Parser
     *
     * @throws UndefinedProductionError
     */
    public function ref($name)
    {
        if ($this->_completed) {
            if (isset($this->_defined[$name])) {
                return $this->_defined[$name];
            } else {
                throw new UndefinedProductionError($name);
            }
        } else {
            if (! isset($this->_undefined[$name])) {
                $this->_undefined[$name] = failure("Uninitalized production: $name")->setable();
            }

            return $this->_undefined[$name];
        }
    }

    /**
     * Defines a production with a [name] and a [parser]. Only call this method
     * from [initialize].
     *
     * The following example defines a list production that consumes
     * several elements separated by a comma.
     *
     *     def('list', ref('element').separatedBy(char(',')));
     *
     * @param string $name
     * @param Parser $parser
     *
     * @return Parser
     *
     * @throws CompletedParserError
     * @throws RedefinedProductionError
     */
    public function def($name, Parser $parser)
    {
        if ($this->_completed) {
            throw new CompletedParserError();
        }

        if (isset($this->_defined[$name])) {
            throw new RedefinedProductionError($name);
        }

        return $this->_defined[$name] = $parser;
    }

    /**
     * Redefinies an existing production with a [name] and a [replacement]
     * parser or function producing a new parser. The code raises an
     * [UndefinedProductionError] if [name] is an undefined production. Only call
     * this method from [initialize].
     *
     * The following example redefines the previously defined list production
     * by making it optional:
     *
     *     redef('list', (parser) => parser.optional());
     *
     * @param string $name
     * @param callable|Parser $replacement replacement Parser, or a proxy function returning a Parser instance
     *
     * @return void
     *
     * @throws UndefinedProductionError
     * @throws CompletedParserError
     */
    public function redef($name, $replacement)
    {
        if ($this->_completed) {
            throw new CompletedParserError();
        }

        if (! isset($this->_defined[$name])) {
            throw new UndefinedProductionError($name);
        }

        $this->_defined[$name] = $replacement instanceof Parser
            ? $replacement
            : call_user_func($replacement, $this->_defined[$name]);
    }

    /**
     * Attaches an action [function] to an existing production [name]. The code
     * raises an [UndefinedProductionError] if [name] is an undefined production.
     * Only call this method from [initialize].
     *
     * The following example attaches an action returning the size of list of
     * the previously defined list production:
     *
     *     action('list', (list) => list.length);
     *
     * @param string $name
     * @param callable $function
     *
     * @return void
     */
    public function action($name, $function)
    {
        $this->redef(
            $name,
            function (Parser $parser) use ($function) {
                return $parser->map($function);
            }
        );
    }


    /**
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @ignore
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->_defined[$offset]);
    }

    /**
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @ignore
     *
     * @param string $offset
     *
     * @return Parser
     */
    public function offsetGet($offset)
    {
        return $this->ref($offset);
    }

    /**
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @ignore
     *
     * @param string $offset
     * @param Parser $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->redef($offset, $value);
    }

    /**
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @ignore
     *
     * @param string $offset
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public function offsetUnset($offset)
    {
        throw new RuntimeException("cannot remove parser from composite");
    }
}
