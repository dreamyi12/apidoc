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
use Psr\Container\ContainerInterface;
use function PHPUnit\Framework\isJson;


/**
 * Class Validator
 * @package Dreamyi12\ApiDoc\Validation
 */
class Validator implements ValidationInterface
{
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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Validator constructor.
     */
    public function __construct(ValidatorFactory $validatorFactory, TranslatorFactory $translatorFactory, ContainerInterface $container)
    {
        $this->validator = $validatorFactory;
        $this->translator = $translatorFactory;
        $this->container = $container;
    }


    /**
     *
     * @param array $rules
     * @param array $data
     * @param array $otherData
     * @param object|null $controller
     * @return array
     * @throws \League\Flysystem\FileExistsException
     */
    public function validate(array $rules, array $data, array $otherData = [], object $controller = null): array
    {
        $allData = array_merge($otherData, $data);
        [$newData, $frameError] = $this->frameValidate($allData, $rules);

        $newData = array_merge($newData, $this->handleFileUpload($rules['path']));
        $newData = $this->handleFunction($newData, is_array($rules['function']) ? $rules['function'] : get_object_vars($rules['function']));
        $errors = [];
        Context::set('validator.data', $newData);
        $data = array_merge($data, $newData);
        //再执行自定义验证
        $error = $this->customsValidate($data, $rules['customs']);
        $errors = array_merge($errors, $frameError, $error);
        return [$data, $errors];
    }

    /**
     * Custom validation processing
     * @param $data
     * @param $rules
     * @return array
     * @throws ValidationException
     */
    private function customsValidate($data, $rules)
    {
        $errors = [];
        foreach ($rules as $fields => $customRules) {
            if (empty($customRules)) continue;
            [$field, $filed_name] = explode('|', $fields);
            $fieldValue = data_get($data, $field);
            if (empty($fieldValue) || (is_array($fieldValue)) && empty(end($fieldValue))) continue;
            foreach ($customRules as $customRule) {
                $ruleName = ApiAnnotation::parseRuleName($customRule);
                //获得规则中的
                $optionParams = explode(':', $customRule)[1] ?? '';
                $optionParam = explode(',', $optionParams);
                if ($optionParams == '' && empty($optionParams)) {
                    array_push($optionParam, '');
                }
                $customValidator = $this->container->get(ApiAnnotation::class)->getCustomValidator();
                if (!isset($customValidator[$ruleName])) {
                    throw new ValidationException("The set validation rule `{$ruleName}` does not exist");
                }
                [$check, $err] = make($customValidator[$ruleName])->handle($data, $fieldValue, $filed_name, $optionParam);
                if (!$check) array_push($errors, $err);
            }
        }
        return $errors;
    }

    /**
     * 框架自带验证
     * @param $data
     * @param $rules
     * @return array
     */
    private
    function frameValidate($data, $rules): array
    {
        $customAttributes = $rule = [];
        foreach ($rules['frames'] as $fields => $frames) {
            [$field, $customAttribute] = explode('|', $fields);
            $customAttributes[$field] = $customAttribute;
            $rule[$field] = $frames;
        }
        //Set picture validation rules
        foreach ($rules['path'] as $field => $path) {
            $data[$field] = $this->requestInterface->file($field);
        }
        $validator = $this->validator->make($data, $rule, [], $customAttributes);
        return [$validator->validate(), $validator->errors()->getMessages()];
    }

    /**
     * Process image upload
     * @param $paths
     * @return array
     * @throws \League\Flysystem\FileExistsException
     */
    private
    function handleFileUpload($paths): array
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
        return isset($newData) ? $newData : [];
    }

    /**
     * Handling custom functions
     * @param $data
     * @param $functions
     * @return array
     */
    private
    function handleFunction($data, $functions): array
    {
        foreach ($functions as $field => $function) {
            if (empty($data[$field]) || !function_exists($function))
                continue;
            $data[$field] = $function($data[$field]);

        }
        return $data;
    }

}