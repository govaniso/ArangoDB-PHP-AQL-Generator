<?php

namespace tarsys\AqlGen;

/**
 * Class to implement UPDATE Operation
 *
 * @author Tarsis Lima
 */
class AqlReturn extends AbstractAql
{
    const OPERATOR = 'RETURN';

    protected $document;

    public function __construct($document)
    {
        $this->document = $document;
    }

    public function get()
    {
        return self::OPERATOR . " {$this->document}";
    }
}
