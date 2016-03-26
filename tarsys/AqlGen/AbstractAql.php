<?php
/**
 * Created by PhpStorm.
 * User: tarsis
 * Date: 10/24/14
 * Time: 1:11 PM
 */

namespace tarsys\AqlGen;

abstract class AbstractAql
{
    const TAB_SEPARATOR = "\t";
    const EXPRESSION_DELIMITER = ':=';

    protected $params = array();

    abstract public function get();

    /**
     * return expression delimited expression
     */
    public static function expr($expr)
    {
        return self::EXPRESSION_DELIMITER . $expr . self::EXPRESSION_DELIMITER;
    }

    /**
     * Set a list of params to bind
     *
     * @param Array $params Key => values of variables to bind
     * eg: $query->bindParams(array('name' => 'john', 'status' => 'OK'));
     * @return string
     */
    public function bindParams($params)
    {
        if (!empty($params)) {
            $this->params = array_merge($this->params, $params);
        }
        return $this;
    }

    /**
     * Set a specific param to bind
     * @return string
     */
    public function bindParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Get all params to bind
     * @return Array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * for to reset params before performs get() method in 'inner' queries
     */
    protected function resetParams()
    {
        $this->params = array();
    }

    protected function normalizeDocument()
    {
        if (is_string($this->document)) {
            $this->document = '"' . $this->document . '"';
        }

        if (is_array($this->document)) {
            $this->document = json_encode($this->document);
            $this->document = $this->fixDocumentKeywords($this->document);
        }
    }

    /**
     * remove quotation marks of reserved words in documents string
     */
    protected function fixDocumentKeywords($document)
    {
        $reserved = array(
            '"_key"' => '_key',
            '"_rev"' => '_rev',
            '"NEW"' => 'NEW',
            '"OLD"' => 'OLD',
            '"NEW._key"' => 'NEW._key',
            '"OLD._key"' => 'OLD._key',
        );

        return str_replace(array_keys($reserved), array_values($reserved), $document);
    }
}
