<?php

namespace Dreamyi12\ApiDoc\Enum;

use Hyperf\Constants\AbstractConstants;

class EnumConstants extends AbstractConstants
{
    /**
     * @var array
     */
    protected $correspond = [];
    /**
     * @var array
     */
    protected $enum = [];

    /**
     * EnumConstants constructor.
     */
    public function __construct()
    {
        $objClass = new \ReflectionClass($this);
        $arrConst = $objClass->getConstants();
        foreach ($arrConst as $value) {
            $this->correspond[static::getText($value)] = $value;
            $this->enum[$value] = static::getText($value);
        }
    }

    /**
     * Get enumeration values from text
     *
     * @param $text
     * @return mixed|null
     */
    public static function getTextValue($text)
    {
        $option = self::getStatic()->correspond;
        return isset($option[$text]) ? $option[$text] : null;
    }

    /**
     * Get content from enumeration values
     * @param $value
     * @return array
     */
    public static function getValueText($value)
    {
        return ['value' => $value, 'text' => self::getStatic()->enum[$value]];
    }

    /**
     * Get all enumerated array
     * @return array
     */
    public static function getEnums()
    {
        return self::getStatic()->enum;
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