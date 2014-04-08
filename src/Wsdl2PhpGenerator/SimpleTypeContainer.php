<?php
/**
 * SimpleTypeContainer.php
 *
 * @copyright 2014 Lamoda.ru
 * @filesource
 */

namespace Wsdl2PhpGenerator;

class SimpleTypeContainer
{
    private $simpleTypeMap = [];
    private $complexTypeMap = [];

    /**
     * Add complex type and its simple base to map.
     *
     * @param string $complexName Complex name.
     * @param string $simpleName Base name.
     */
    public function mapSimpleType($complexName, $simpleName) {
        $this->simpleTypeMap[$complexName] = $simpleName;
    }

    /**
     * Add complex type and its parent to map.
     *
     * @param string $complexName Complex type name.
     * @param string $complexBase Base complex type name.
     */
    public function mapComplexType($complexName, $complexBase) {
        $this->complexTypeMap[$complexName] = $complexBase;
    }

    /**
     * Find simple base for this type.
     * Types like "string" are value types in php and this resolves any type that extends "string" back to "string".
     *
     * @param string $complexName Complex type name.
     * @return null|string
     */
    public function findSimpleBase($complexName) {
        $simpliestComplex = $complexName;
        while (isset($this->complexTypeMap[$simpliestComplex])) {
            $simpliestComplex = $this->complexTypeMap[$simpliestComplex];
        }
        if (isset($this->simpleTypeMap[$simpliestComplex])) {
            return $this->simpleTypeMap[$simpliestComplex];
        }
        return null;
    }

    /**
     * Check if type is in complex map.
     *
     * @param string $complexName
     * @return bool
     */
    public function isTypeComplex($complexName) {
        return isset($this->complexTypeMap[$complexName]);
    }

}
