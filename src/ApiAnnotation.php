<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/6
 * Time: 16:34
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc;

use ArrayAccess;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Dreamyi12\ApiDoc\Annotation\ApiController;
use Dreamyi12\ApiDoc\Annotation\ApiVersion;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;
use Hyperf\Utils\Arr;
use Kph\Helpers\ArrayHelper;
use Kph\Helpers\StringHelper;
use Kph\Helpers\ValidateHelper;


/**
 * Class ApiAnnotation
 * @package Dreamyi12\ApiDoc
 */
class ApiAnnotation
{

    /**
     * @var string
     */
    public static $schemaMethodPrefix = 'getSchema';

    /**
     * @var object
     */
    protected object $routeCache;

    /**
     * @var array
     */
    protected array $enumsClass;

    /**
     * @var array 
     */
    protected array $customValidator;

    /**
     * Get metadata of class
     * @param string $className
     * @return array|ArrayAccess|mixed|null
     */
    public static function getClassMetadata(string $className)
    {
        return AnnotationCollector::getClassAnnotation($className, ApiController::class);
    }

    /**
     * Get metadata of version number
     * @param string $className
     * @return array
     */
    public static function getVersionMetadata(string $className)
    {
        $refCls = ReflectionManager::reflectClass($className);
        $reader = new AnnotationReader();
        //Get the annotation class information according to the reflection class
        $clsAnnotations = $reader->getClassAnnotations($refCls);
        if (!is_array($clsAnnotations) || empty($clsAnnotations)) return [];

        foreach ($clsAnnotations as $clsAnnotation) {
            //Get the annotation class whose object is API version in the annotation class
            if ($clsAnnotation instanceof ApiVersion) {
                if (empty($clsAnnotation->group)) {
                    $result['0'] = $clsAnnotation;
                } else {
                    $result[$clsAnnotation->group] = $clsAnnotation;
                }
            }
        }
        return Arr::sortRecursive($result);
    }

    /**
     * Get metadata of method
     * @param string $className
     * @param string $methodName
     * @return array|object[]
     */
    public static function getMethodMetadata(string $className, string $methodName)
    {
        $reflectMethod = ReflectionManager::reflectMethod($className, $methodName);
        $reader = new AnnotationReader();
        return $reader->getMethodAnnotations($reflectMethod);
    }

    /**
     * Set routing cache
     * @param object $cache
     */
    public function setRouteCache(object $cache): void
    {
        $this->routeCache = $cache;
    }

    /**
     * Get routing cache
     * @return object
     */
    public function getRouteCache(): object
    {
        return $this->routeCache;
    }

    /**
     * @return array
     */
    public function getEnumsClass(): array
    {
        return $this->enumsClass;
    }

    /**
     * @param array $enumsClass
     */
    public function setEnumsClass(array $enumsClass): void
    {
        $this->enumsClass = $enumsClass;
    }

    /**
     * @return array
     */
    public function getCustomValidator(): array
    {
        return $this->customValidator;
    }

    /**
     * @param array $customValidator
     */
    public function setCustomValidator(array $customValidator): void
    {
        $this->customValidator = $customValidator;
    }

    
    /**
     * @param string $rule
     * @return array
     */
    public static function parseByRule(string $rule): array
    {
        $arr = explode('|', $rule);
        array_walk($arr, function (&$item) {
            $item = strtolower(trim($item));
            return $item;
        });

        return array_unique(array_filter($arr));
    }


    /**
     *
     * @param string $str
     * @return string
     */
    public static function parseRuleName(string $str): string
    {
        $res = preg_replace('/\[.*\]/', '', $str);
        if (strpos($res, ':')) {
            $arr = explode(':', $res);
            $res = $arr[0];
        }
        return trim($res);
    }
}