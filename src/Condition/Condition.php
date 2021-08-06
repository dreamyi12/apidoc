<?php

namespace Dreamyi12\ApiDoc\Condition;


use Hyperf\Utils\Context;

class Condition
{
    /**
     * Conditional parameter object array
     * @var array
     */
    private $whereParams;

    /**
     * Condition constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialization parameters
     */
    public function initialize()
    {
        $where = self::getValidatorWhere();
        $data = self::getValidatorData();
        foreach ($where as $field => $option) {
            if (empty($option)) continue;

            $whereParam = new WhereParams();
            foreach ($option as $key => $value) {
                $method = "set" . ucfirst($key);
                if (method_exists($whereParam, $method)) {
                    $whereParam->{$method}($value);
                }

            }
            if (!$whereParam->getValue() && isset($data[$field]))
                $whereParam->setValue($data[$field]);

            if (empty($whereParam->getValue())) {
                continue;
            }
            $this->whereParams[$field] = $whereParam;
        }
    }

    /**
     * Get validation parameters
     * @return array
     */
    public static function getValidatorData(): array
    {
        return !empty(Context::get('validator.data')) ? Context::get('validator.data') : [];
    }

    /**
     * Get condition parameters
     * @return array
     */
    public static function getValidatorWhere(): array
    {
        return !empty(Context::get('validator.where')) ? Context::get('validator.where') : [];
    }

    /**
     * Get condition parameters
     * @return array
     */
    public function getWhereParams()
    {
        return $this->whereParams;
    }
}