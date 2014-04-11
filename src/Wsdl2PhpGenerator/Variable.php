<?php
/**
 * @package Wsdl2PhpGenerator
 */
namespace Wsdl2PhpGenerator;

/**
 * Very stupid datatype to use instead of array
 *
 * @package Wsdl2PhpGenerator
 * @author Fredrik Wallgren <fredrik.wallgren@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
class Variable
{
    /**
     * @var string The type
     */
    private $type;

    /**
     * @var string The name
     */
    private $name;

    /**
     * @var boolean Nillable
     */
    private $nillable;

    /**
     * @param string $type
     * @param string $name
     * @param bool $nillable
     */
    public function __construct($type, $name, $nillable)
    {
        $this->type = $type;
        $this->name = $name;
        $this->nillable = $nillable;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function getNillable()
    {
        return $this->nillable;
    }

    public static function createGetterName($string) {
        if (strlen($string) > 3 && substr($string, 0, 3) === 'get') {
            $string = substr($string, 3);
        }

        return 'get' . self::underscoresToCamelCase($string, true);
    }

    public static function createSetterName($string) {
        if (strlen($string) > 3 && substr($string, 0, 3) === 'get') {
            $string = substr($string, 3);
        }

        return 'set' . self::underscoresToCamelCase($string, true);
    }

    /**
     * Convert underscores to camel case.
     *
     * @param string $string
     * @param bool $capitalizeFirstCharacter
     * @return string
     */
    public static function underscoresToCamelCase($string, $capitalizeFirstCharacter = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));

        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }

        return $str;
    }
}
