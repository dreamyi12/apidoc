<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/6
 * Time: 16:32
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Annotation;

use Dreamyi12\ApiDoc\Annotation\Collector\CustomCollector;
use Dreamyi12\ApiDoc\Annotation\Enums\EnumClass;
use Dreamyi12\ApiDoc\ApiAnnotation;
use Dreamyi12\ApiDoc\Validation\Validator;
use Hyperf\Constants\ConstantsCollector;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Utils\ApplicationContext;


/**
 * Class Params
 * @package Dreamyi12\ApiDoc\Annotation
 */
class Params extends AbstractAnnotation
{


    /**
     * @var string 在哪个结构
     */
    public $in;


    /**
     * @var string 字段key,相当于"name[|description]"
     */
    public $key;


    /**
     * @var string 单个规则字符串
     */
    public $rule;


    /**
     * @var mixed 字段默认值
     */
    public $default;


    /**
     * @var string 字段名
     */
    public $name;


    /**
     * @var string 字段描述
     */
    public $description;


    /**
     * @var array 详细规则数组
     */
    public $_detailRules = [];


    /**
     * @var bool 是否必须
     */
    public $required = false;


    /**
     * @var string 字段类型
     */
    public $type;


    /**
     * @var array 字段枚举值
     */
    public $enum;


    /**
     * @var mixed 字段举例值
     */
    public $example;


    /**
     * @var string 查询条件
     */
    public $where;

    /**
     * @var string 字段
     */
    public $attribute;

    /**
     * @var string 过滤函数
     */
    public $function;

    /**
     * @var string 上传地址
     */
    public $path;

    /**
     * Params constructor.
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        parent::__construct($value);
        $this->setAttribute()->setDescription()->setDetailRules()->setRquire()->setType()->setDefault()->setEnum()->setExample();
    }


    /**
     * 设置key
     * @param string $key
     * @return $this
     */
    public function setKey(string $key = '')
    {
        if (!empty($key)) {
            $this->key = $key;
        }

        return $this;
    }


    /**
     * 设置字段名
     * @param string $filed
     * @return $this
     */
    public function setAttribute(string $attribute = '')
    {
        if (!empty($attribute)) {
            $this->attribute = $attribute;
        } elseif (!empty($this->key)) {
            $filed_info = explode('|', $this->key);
            if (count($filed_info) >= 2) {
                [$this->name, $this->attribute] = explode('|', $this->key);
            } else {
                [$this->name] = explode('|', $this->key);
                $this->attribute = $this->name;
            }
        }
        return $this;
    }


    /**
     * 设置字段描述
     * @param string $desc
     * @return $this
     */
    public function setDescription(string $desc = '')
    {

        $data = AnnotationCollector::list();
        $rules = explode('|', $this->rule);

        if (!empty($desc)) {
            $this->description = $desc;
        } else {
            $this->description = $this->description ?: explode('|', strval($this->key))[1] ?? $this->name;
        }

        return $this;
    }


    /**
     * 设置详细规则数组(将规则串拆分为数组)
     * @return $this
     */
    public function setDetailRules()
    {
        if (!empty($this->rule)) {
            $this->_detailRules = ApiAnnotation::parseByRule($this->rule);
        }

        return $this;
    }


    /**
     * @param string $rule
     * @return string
     */
    public function getTypeByRule(string $rule): string
    {
        $details = ApiAnnotation::parseByRule($rule);
        $type = ['gt', 'gte', 'lt', 'lte', 'max', 'min', 'between'];

        $digitItem = in_array($rule, $type) ? $rule : false;

        if (array_intersect($details, ['integer', 'int'])) {
            return 'integer';
        } elseif (array_intersect($details, ['float'])) {
            return 'float';
        } elseif (array_intersect($details, ['number', 'numeric'])) {
            return 'number';
        } elseif (array_intersect($details, ['boolean', 'bool'])) {
            return 'boolean';
        } elseif (array_intersect($details, ['array'])) {
            return 'array';
        } elseif (array_intersect($details, ['object'])) {
            return 'object';
        } elseif (array_intersect($details, ['file', 'image'])) {
            return 'file';
        } elseif (array_intersect($details, ['string', 'trim'])) {
            return 'string';
        } elseif ($digitItem) {
            foreach ($details as $detail) {
                if (strpos($detail, ':') && stripos($detail, $digitItem) !== false) {
                    //是否有规则选项,如 between:1,20 中的 :1,20
                    preg_match('/:(.*)/', $detail, $match);
                    $options = $match[1] ?? '';
                    $arr = explode(',', $options);
                    $first = $arr[0] ?? '';
                    if (is_float($first)) {
                        return 'float';
                    } elseif (is_integer($first)) {
                        return 'integer';
                    }
                }
            }
        }

        return 'string';
    }

    /**
     * 设置字段是否必填
     * @return $this
     */
    public function setRquire()
    {
        foreach ($this->_detailRules as $detailRule) {
            $ruleName = ApiAnnotation::parseRuleName($detailRule);
            //一定要等于"required",因为还有其他规则名如required_without_all等
            if ($ruleName == 'required') {
                $this->required = true;
                break;
            }
        }

        return $this;
    }


    /**
     * 设置字段类型
     * @return $this
     */
    public function setType()
    {
        $type = '';
        if (in_array('int', $this->_detailRules) || in_array('integer', $this->_detailRules)) {
            $type = 'integer';
        } elseif (in_array('float', $this->_detailRules)) {
            $type = 'float';
        } elseif (in_array('number', $this->_detailRules) || in_array('numeric', $this->_detailRules)) { // numeric 是hyperf官方验证规则
            $type = 'number';
        } elseif (in_array('bool', $this->_detailRules) || in_array('boolean', $this->_detailRules)) { // boolean 是hyperf官方验证规则
            $type = 'boolean';
        } elseif (in_array('array', $this->_detailRules)) { // array 是hyperf官方验证规则
            $type = 'array';
        } elseif (in_array('object', $this->_detailRules)) { // object 是swagger的数据类型
            $type = 'object';
        }

        if (empty($type)) {
            $type = $this->getTypeByRule($this->rule);
        }

        $this->type = $type;

        return $this;
    }


    /**
     * 设置字段默认值
     * @return $this
     */
    public function setDefault()
    {
        return $this;
    }


    /**
     * 设置字段枚举值
     * @return $this
     */
    public function setEnum()
    {
        if (empty($this->_detailRules)) {
            $this->setDetailRules();
        }
        foreach ($this->_detailRules as $detailRule) {
            if (stripos($detailRule, 'enum') !== false) {
                $optionStr = explode(':', $detailRule)[1] ?? '';
                $optionArr = explode(',', $optionStr);

                $this->enum = $optionArr;
                break;
            }
        }

        return $this;
    }


    /**
     * 设置字段举例值
     * @return $this
     */
    public function setExample()
    {
        if (empty($this->_detailRules)) {
            $this->setDetailRules();
        }
        if (empty($this->type)) {
            $this->setType();
        }

        if (!empty($this->example)) {
            $val = strval($this->example);
            switch ($this->type) {
                case 'integer':
                    $val = Validator::conver_int($val);
                    break;
                case 'float':
                    $val = Validator::conver_float($val);
                    break;
                case 'boolean':
                    $val = Validator::conver_boolean($val);
                    break;
                default:
                    break;
            }
            $this->example = $val;
        }

        return $this;
    }
}