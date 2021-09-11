<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/9
 * Time: 16:02
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Validation;

use Dreamyi12\ApiDoc\ApiAnnotation;
use Dreamyi12\ApiDoc\Exception\ValidationException;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Server\Exception\RuntimeException;
use Hyperf\Translation\TranslatorFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Context;
use Hyperf\Validation\Concerns\ValidatesAttributes;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidatorFactory;
use Kph\Helpers\ArrayHelper;
use Kph\Helpers\StringHelper;
use Kph\Helpers\ValidateHelper;
use League\Flysystem\Filesystem;


/**
 * Class Validator
 * @package Dreamyi12\ApiDoc\Validation
 */
class Validator implements ValidationInterface
{

    /**
     * 验证回调方法规则前缀(供自定义控制器的验证方法使用),前缀之后就是具体的控制器方法名称.如:
     * 规则'cb_checkName',即为要调用控制器的方法checkName去做验证.
     * @var string
     */
    public static $validateCallbackPrefix = 'cb_';


    /**
     * @Inject
     * @var ValidatorFactoryInterface
     */
    public $validator;


    /**
     * @Inject
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * @Inject
     * @var Filesystem
     */
    protected $filesystem;


    /**
     * Validator constructor.
     */
    public function __construct()
    {
        if (is_null($this->validator)) {
            $this->validator = ApplicationContext::getContainer()->get(ValidatorFactory::class);
        }
        if (is_null($this->translator)) {
            $this->translator = ApplicationContext::getContainer()->get(TranslatorFactory::class);
        }
    }


    /**
     * 合并数据,将新数据更新到源数据中.
     * @param array $origin 源数据
     * @param array $new 新数据
     * @return array
     */
    public static function combineData(array $origin, array $new = []): array
    {
        if (empty($new)) {
            return $origin;
        }

        foreach ($origin as $k => $item) {
            if (isset($new[$k])) {
                $origin[$k] = $new[$k];
            }
        }

        return $origin;
    }


    /**
     * 重新排序(某字段的)详细规则数组(类型检查放在前面)
     * @param array $rules
     * @return array
     */
    public static function sortDetailRules(array $rules): array
    {
        $priorities = ['default', 'required', 'int', 'integer', 'bool', 'boolean', 'number', 'numeric', 'float', 'string', 'array', 'object'];
        $res = [];

        foreach ($rules as $rule) {
            $lowRule = strtolower($rule);
            if (in_array($lowRule, $priorities)) {
                if ($lowRule == 'int') {
                    $rule = 'integer';
                } elseif ($lowRule == 'bool') {
                    $rule = 'boolean';
                } elseif ($lowRule == 'number') {
                    $rule = 'numeric';
                }

                array_unshift($res, $rule);
            } else {
                array_push($res, $rule);
            }
        }

        return $res;
    }

    /**
     * 处理数据
     * @param $data
     * @return mixed
     */
    public function handleData($data)
    {
        //抛弃掉空值
        foreach ($data as $field => $value) {
            if ($value == "" || empty($value) || is_null($value)) unset($data[$field]);
        }
        return $data;
    }

    /**
     * 框架自带验证
     * @param $data
     * @param $rules
     * @return array
     */
    public function framesValidate($data, $rules)
    {
        $map = $rule = [];
        foreach ($rules['frames'] as $fields => $frames) {
            [$field, $map] = explode('|', $fields);
            $map[$field] = $map;
            $rule[$field] = $frames;
        }
        //设置图片验证规则
        foreach ($rules['path'] as $field => $path) {
            $data[$field] = $this->requestInterface->file($field);
        }
        $validator = $this->validator->make($data, $rule, [], $map);
        return [$validator->validate(), $validator->errors()->getMessages()];
    }

    /**
     * Process image upload
     * @param $paths
     * @return array
     * @throws \League\Flysystem\FileExistsException
     */
    public function handleFileUpload($paths)
    {
        foreach ($paths as $field => $path) {
            $files = $this->requestInterface->file($field);
            if (!empty($files)) {
                $stream = fopen($files->getRealPath(), 'r+');
                $path = $path . md5($files->getClientFilename() . "QcJun" . time()) . "." . $files->getExtension();
                if (!$this->filesystem->has($path)) {
                    $this->filesystem->writeStream($path, $stream);
                }
                $newData[$field] = $path;
            }
        }
        return $newData;
    }


    protected function customsValidate($data, $rules)
    {
        foreach ($rules as $fields => $customRuleArr) {
            if (empty($customRuleArr)) {
                continue;
            }
            [$field, $filed_name] = explode('|', $fields);
            foreach ($customRuleArr as $customRule) {
                //首先检查默认规则
                $detailRules = explode('|', $customRule);
                [$detailRules, $fieldValue] = self::checkDefault($data, $detailRules, $field, $fieldValue);

                foreach ($detailRules as $detailRule) {
                    $ruleName = ApiAnnotation::parseRuleName($detailRule);
                    $optionStr = explode(':', $detailRule)[1] ?? '';
                    $optionArr = explode(',', $optionStr);
                    if ($optionStr == '' && empty($optionArr)) {
                        array_push($optionArr, '');
                    }
                    $ruleMethod = 'rule_' . $ruleName;
                    if (method_exists($this, $ruleMethod)) {
                        [$check, $err] = call_user_func_array([$this, $ruleMethod,], [$fieldValue, $field, $filed_name, $optionArr]);
                        if (!$check) {
                            array_push($errors, $err);
                            break;
                        }
                    }
                }
            }

            if (!is_null($fieldValue)) {
                ArrayHelper::setDotKey($data, $field, $fieldValue);
            }
        }
    }


    /**
     * 进行验证
     * @param array $rules
     * @param array $data
     * @param array $otherData
     * @param object|null $controller
     * @return array 结果,形如[data, errors]
     * @throws ValidationException
     */
    public function validate(array $rules, array $data, array $otherData = [], object $controller = null): array
    {
        $function = $rules['function'] ?? [];
        $customRules = $rules['customs'] ?? [];
        $allData = array_merge($otherData, $data);
        $errors = [];
        [$newData, $frameError] = $this->framesValidate($this->handleData($allData), $rules);
        $newData = array_merge($newData, $this->handleFileUpload($rules['path']));
        //使用自定义函数处理
        foreach ($function as $function_field => $function) {
            if (empty($newData[$function_field]) || !function_exists($function)) continue;
            $newData[$function_field] = $function($newData[$function_field]);
        }
        Context::set('validator.data', $newData);
        $data = self::combineData($data, $newData);
        //再执行自定义验证

        $errors = array_merge($errors, $frameError);
        return [$data, $errors];
    }


    /**
     * 检查数据的字段是否有默认规则,若有则设置,然后删除默认规则
     * @param array $data 数据
     * @param array $rules 规则
     * @param string $field 字段
     * @param mixed $val 字段值
     * @return array
     */
    public static function checkDefault(array $data, array $rules, string $field, $val = null): array
    {
        if (is_null($val) || $val === '') {
            foreach ($rules as $k => $rule) {
                $ruleName = ApiAnnotation::parseRuleName($rule);
                $optionStr = explode(':', $rule)[1] ?? '';
                $optionArr = explode(',', $optionStr);
                if ($optionStr == '' && empty($optionArr)) {
                    array_push($optionArr, '');
                }
                if ($ruleName == 'default') {
                    $val = self::conver_default($val, $optionArr);

                    unset($rules[$k]);
                    break;
                }
            }
        }

        return [$rules, $val];
    }

    /**
     * 验证-枚举
     * @param $val
     * @param string $field
     * @param array $options
     * @return array [bool, err]
     */
    public function rule_enum($val, string $name, array $options = []): array
    {
        $err = '';
        if (empty($options)) {
            return [true, $err];
        }
        [$enumName] = $options;
        $enumClass = ApplicationContext::getContainer()->get(ApiAnnotation::class)->getEnums();
        $enums = $enumClass[$enumName]::getEnums();
        $err = $this->translator->trans('validation.enum', ['attribute' => $name]);
        if (is_array($val)) {
            foreach ($val as $item) {
                if (!isset($enums[$item])) {
                    return [false, $err];
                }
            }
        } else {
            return [isset($enums[$val]) ? true : false, $err];
        }
        return [true, $err];
    }


    /**
     * 验证-对象(键值对数组)
     * @param $val
     * @param string $field
     * @param array $options
     * @return array [bool, err]
     */
    public function rule_object($val, string $field, string $name, array $options = []): array
    {
        $err = '';

        // 必须是数组
        if (!is_array($val)) {
            $err = $this->translator->trans('apihelper.rule_object', ['field' => $name]);
            return [false, $err];
        }

        // 键值不能是数字
        $keys = array_keys($val);
        foreach ($keys as $key) {
            if (is_integer($key)) {
                $err = $this->translator->trans('apihelper.rule_object', ['field' => $field]);
                return [false, $err];
            }
        }

        return [true, $err];
    }


    /**
     * 验证-自然数
     * @param $val
     * @param string $field
     * @param array $options
     * @return array [bool, err]
     */
    public function rule_natural($val, string $field, string $name, array $options = []): array
    {
        $err = '';
        $chk = ValidateHelper::isNaturalNum($val);
        if (!$chk) {
            $err = $this->translator->trans('apihelper.rule_natural', ['field' => $name]);
        }

        return [boolval($chk), $err];
    }


    /**
     * 验证-中国手机号
     * @param $val
     * @param string $field
     * @param array $options
     * @return array [bool, err]
     */
    public function rule_cnmobile($val, string $field, string $name, array $options = []): array
    {
        $err = '';
        $chk = ValidateHelper::isMobilecn(strval($val));
        if (!$chk) {
            $err = $this->translator->trans('apihelper.rule_cnmobile', ['field' => $name]);
        }

        return [boolval($chk), $err];
    }


    /**
     * 验证-中国身份证号
     * @param $val
     * @param string $field
     * @param array $options
     * @return array [bool, err]
     */
    public function rule_cncreditno($val, string $field, string $name, array $options = []): array
    {
        $err = '';
        $chk = ValidateHelper::isChinaCreditNo(strval($val));
        if (!$chk) {
            $err = $this->translator->trans('apihelper.rule_cncreditno', ['field' => $name]);
        }

        return [boolval($chk), $err];
    }


    /**
     * 验证-安全密码
     * @param $val
     * @param string $field
     * @param array $options
     * @return array [bool, err]
     */
    public function rule_safe_password($val, string $field, string $name, array $options = []): array
    {
        $err = '';
        $level = StringHelper::passwdSafeGrade(strval($val));
        if ($level < 2) {
            $err = $this->translator->trans('apihelper.rule_safe_password_simple', ['field' => $name]);
            return [false, $err];
        }

        return [true, $err];
    }


}