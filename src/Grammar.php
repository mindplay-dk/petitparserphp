<?php

namespace petitparser;

abstract class Grammar extends DelegateParser
{
    /**
     * @var SettableParser[]
     */
    private $_refs = array();

    /**
     * Initializes the grammar; to be implemented by concrete grammar classes.
     *
     * @return Parser root/start parser
     */
    abstract protected function init();

    public function __construct()
    {
        parent::__construct(failure('Uninitalized production: start'));

        $this->_delegate = removeSettables($this->init());

        foreach ($this->_refs as $old) {
            $new = removeSettables($old);

            foreach ($this->_refs as $child) {
                $child->replace($old, $new);
            }
        }
    }

    /**
     * @return SettableParser
     */
    protected function ref()
    {
        $ref = failure("Uninitalized production")->settable();

        $this->_refs[] = $ref;

        return $ref;
    }
}
