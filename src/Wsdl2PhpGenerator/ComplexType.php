<?php

/**
 * @package Generator
 */
namespace Wsdl2PhpGenerator;

use \Exception;
use Wsdl2PhpGenerator\PhpSource\PhpClass;
use Wsdl2PhpGenerator\PhpSource\PhpDocComment;
use Wsdl2PhpGenerator\PhpSource\PhpDocElementFactory;
use Wsdl2PhpGenerator\PhpSource\PhpFunction;
use Wsdl2PhpGenerator\PhpSource\PhpVariable;

/**
 * ComplexType
 *
 * @package Wsdl2PhpGenerator
 * @author Fredrik Wallgren <fredrik.wallgren@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
class ComplexType extends Type
{

    /**
     * The members in the type
     *
     * @var array
     */
    private $members;

    /**
     * Simple type container.
     *
     * @var SimpleTypeContainer
     */
    private $simpleTypes;

    /**
     * Construct the object
     *
     * @param ConfigInterface $config The configuration
     * @param string $name The identifier for the class
     * @param SimpleTypeContainer $simpleTypes Simple type container
     */
    public function __construct(ConfigInterface $config, $name, SimpleTypeContainer $simpleTypes)
    {
        parent::__construct($config, $name, null);
        $this->members = array();
        $this->simpleTypes = $simpleTypes;
    }

    private function getSoapTypeName($type) {
        if ('datetime' === strtolower($type)) {
            return 'string';
        }
        if ('decimal' === strtolower($type)) {
            return 'string';
        }
        if ($this->simpleTypes->isTypeComplex($type)) {
            return Variable::underscoresToCamelCase($type, true);
        }
        return $type;
    }

    private function getClientTypeName($type) {
        if ('datetime' === strtolower($type)) {
            return '\\DateTime';
        }
        if ('decimal' === strtolower($type)) {
            return 'string';
        }
        if ($this->simpleTypes->isTypeComplex($type)) {
            return Variable::underscoresToCamelCase($type, true);
        }
        return $type;
    }

    private function getClientTypeComment($type) {
        if ('decimal' === strtolower($type)) {
            return 'Decimal';
        }
        return '';
    }

    private function generateVarToSoapSource($paramName, $type) {
        if ('datetime' === strtolower($type)) {
            return 'null === ' . $paramName . ' ? null : ' . $paramName . '->format("c")';
        }
        return $paramName;
    }

    private function generateSoapToVarSource($paramName, $type) {
        if ('datetime' === strtolower($type)) {
            return 'null === ' . $paramName . ' ? null : new \\DateTime(' . $paramName . ')';
        }
        return $paramName;
    }

    /**
     * Getter generator.
     *
     * @param string $name
     * @param string $type
     * @return PhpFunction
     */
    private function createGetterFunction($name, $type) {
        $getterComment = new PhpDocComment();
        $getterComment->setReturn(PhpDocElementFactory::getReturn($this->getClientTypeName($type), $this->getClientTypeComment($type)));

        return new PhpFunction(
            'public',
            Variable::createGetterName($name),
            '',
            '  return ' . $this->generateSoapToVarSource('$this->' . $name, $type) . ';' . PHP_EOL,
            $getterComment
        );
    }

    /**
     * Setter generator.
     *
     * @param string $name
     * @param string $type
     * @return PhpFunction
     * @throws \Exception
     */
    private function createSetterFunction($name, $type) {
        $setterComment = new PhpDocComment();
        $paramName = Variable::underscoresToCamelCase($name);

        $setterComment->addParam(PhpDocElementFactory::getParam($this->getClientTypeName($type), $paramName, $this->getClientTypeComment($type)));
        $setterComment->setReturn(PhpDocElementFactory::getReturn($this->phpIdentifier, ''));
        return new PhpFunction(
            'public',
            Variable::createSetterName($name),
            '$' . $paramName,
            '  $this->' . $name . ' = ' . $this->generateVarToSoapSource('$' . $paramName, $type) . ';' . PHP_EOL .
            '  return $this;' . PHP_EOL
            ,
            $setterComment
        );
    }

    /**
     * Create field variable.
     *
     * @param string $name
     * @param string $type
     * @return PhpVariable
     */
    private function createField($name, $type) {
        $arrayMark = substr($type, -2);
        $isArray = '[]' == $arrayMark;

        $comment = new PhpDocComment();
        $comment->setVar(PhpDocElementFactory::getVar($this->getSoapTypeName($type), $name, ''));
        $comment->setAccess(PhpDocElementFactory::getPublicAccess());
        return new PhpVariable(
            $this->config->getCreateAccessors() ? 'private' : 'public',
            $name,
            $isArray ? '[]' : 'null',
            $comment
        );
    }

    /**
     * Create constructor parameter.
     *
     * @param string $name
     * @param string $type
     * @param PhpDocComment $constructorComment
     * @param string $constructorSource
     * @param string $constructorParameters
     * @throws \Exception
     */
    private function appendConstructorParameter($name, $type, $constructorComment, &$constructorSource, &$constructorParameters) {
        $arrayMark = substr($type, -2);
        $isArray = '[]' == $arrayMark;

        $paramName = Variable::underscoresToCamelCase($name);

        $constructorSource .= '  $this->' . $name . ' = ' . $this->generateVarToSoapSource('$' . $paramName, $type) . ';' . PHP_EOL;
        $constructorComment->addParam(PhpDocElementFactory::getParam($this->getClientTypeName($type), $paramName, $this->getClientTypeComment($type)));
        $constructorComment->setAccess(PhpDocElementFactory::getPublicAccess());
        $constructorParameters .= ', $' . $paramName;
        if ($this->config->getConstructorParamsDefaultToNull()) {
            $constructorParameters .= ' = ' . ($isArray ? '[]' : 'null');
        }
    }

    /**
     * Implements the loading of the class object
     *
     * @throws Exception if the class is already generated(not null)
     */
    protected function generateClass()
    {
        if ($this->class != null) {
            throw new Exception("The class has already been generated");
        }

        $classComment = new PhpDocComment();
        $classComment->setDescription('This class was most likely auto-generated and you should not modify it directly.');

        $class = new PhpClass($this->phpIdentifier, $this->config->getClassExists(), '', $classComment);

        $constructorComment = new PhpDocComment();
        $constructorComment->setAccess(PhpDocElementFactory::getPublicAccess());
        $constructorSource = '';
        $constructorParameters = '';
        $accessors = array();

        // Add member variables
        foreach ($this->members as $member) {
            $type = '';

            try {
                $type = Validator::validateType($member->getType());
            } catch (ValidationException $e) {
                $type .= 'Custom';
            }

            $arrayMark = substr($type, -2);
            $isArray = '[]' == $arrayMark;
            if ($isArray) {
                $typeWithoutArrayMark = substr($type, 0, strlen($type) - 2);
                $simpleBaseType = $this->simpleTypes->findSimpleBase($typeWithoutArrayMark);
                if (null !== $simpleBaseType) {
                    $typeWithoutArrayMark = $simpleBaseType;
                }
                $type = $typeWithoutArrayMark . '[]';
            } else {
                $simpleBaseType = $this->simpleTypes->findSimpleBase($type);
                if (null !== $simpleBaseType) {
                    $type = $simpleBaseType;
                }
            }

            $name = Validator::validateNamingConvention($member->getName());
            $class->addVariable($this->createField($name, $type));

            if (!$member->getNillable()) {
                $this->appendConstructorParameter($name, $type, $constructorComment, $constructorSource, $constructorParameters);

                if ($this->config->getCreateAccessors()) {
                    $accessors[] = $this->createGetterFunction($name, $type);
                    $accessors[] = $this->createSetterFunction($name, $type);
                }
            }
        }

        $constructorParameters = substr($constructorParameters, 2); // Remove first comma
        $function = new PhpFunction('public', '__construct', $constructorParameters, $constructorSource, $constructorComment);

        // Only add the constructor if type constructor is selected
        if ($this->config->getNoTypeConstructor() == false) {
            $class->addFunction($function);
        }

        foreach ($accessors as $accessor) {
            $class->addFunction($accessor);
        }

        $this->class = $class;
    }

    /**
     * Adds the member. Owerwrites members with same name
     *
     * @param string $type
     * @param string $name
     * @param bool $nillable
     */
    public function addMember($type, $name, $nillable)
    {
        $this->members[$name] = new Variable($type, $name, $nillable);
    }
}
