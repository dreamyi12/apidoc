<?php

namespace Dreamyi12\ApiDoc\Enum;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\ConstantsCollector;

abstract class EnumConstants extends AbstractConstants
{

    /**
     * Get all enumeration types
     * @return array|mixed
     */
    public static function getEnums($value = null)
    {
        $enums = [];
        $class = get_called_class();
        $message = ConstantsCollector::list();
        foreach ($message as $classed => $object) {
            if ($classed == $class) {
                $enums = $object;
                break;
            }
        }
        return $value !== null ? $enums[$value] : $enums;
    }

    /**
     * Obtain all enumeration data according to static methods
     * @param $method_name
     * @param $arguments
     * @return mixed|string
     * @throws \Hyperf\Constants\Exception\ConstantsException
     * @throws \ReflectionException
     */
    public static function __callStatic($method_name, $arguments)
    {
        $methods = strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . '_' . "$2", $method_name));
        $methods = explode('_', $methods);
        if (count($methods) == 3) {
            $objClass = new \ReflectionClass(get_called_class());
            $arrConst = $objClass->getConstants();
            [$mode, $filed, $type] = $methods;
            $correspond = [];
            if ($filed == "value") {
                foreach ($arrConst as $value) {
                    $method = $mode . ucfirst($type);
                    $correspond[$value] = static::$method($value);
                }
            } elseif ($type == "value") {
                foreach ($arrConst as $value) {
                    $method = $mode . ucfirst($filed);
                    $correspond[static::$method($value)] = $value;
                }
            }
            return $correspond[$arguments[0]];
        } else {
            return parent::__callStatic($method_name, $arguments);
        }

    }

    /**
     * Instantiate the current class
     * @return static
     */
    protected static function getStatic()
    {
        return new static();
    }
}