<?php

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Annotation\Collector;

use Hyperf\Di\MetadataCollector;
use Hyperf\Utils\Contracts\Arrayable;

class CustomCollector extends MetadataCollector
{
    /**
     * @var array
     */
    protected static $container = [];

    //类
    public static function collectClass(string $annotation, string $name, $className): void
    {
        // 注解类 关键词 = 实际类
        static::$container[$annotation]['_c'][$name] = $className;
    }

    //属性
    public static function collectProperty(string $annotation, string $className, string $property, $value): void
    {
        // 注解类 收集类 方法 = 属性类
        static::$container[$annotation]['_p'][$className][$property] = $value;
    }

    //方法
    public static function collectMethod(string $annotation, string $className, $method, $value): void
    {
        // 注解类 收集类 方法 = 注解类
        static::$container[$annotation]['_m'][$className][$method] = $value;
    }

    public static function clear(?string $key = null): void
    {
        if ($key) {
            unset(static::$container[$key]);
        } else {
            static::$container = [];
        }
    }

    public static function getAnnotationByClasses(string $annotation, $name = null)
    {
        return $name && isset(static::$container[$annotation]['_c'][$name]) ? static::$container[$annotation]['_c'][$name] : static::$container[$annotation]['_c'];
    }


    public static function getAnnotationByMethods(string $annotation): array
    {
        $result = [];
        foreach (static::$container[$annotation]['_m'] as $class => $metadata) {
            $result[$class] = $metadata;
        }
        return $result;
    }

    public static function getAnnotationByProperties(string $annotation): array
    {
        $result = [];
        foreach (static::$container[$annotation]['_p'] as $class => $metadata) {
            $result[$class] = $metadata;
        }
        return $result;
    }
}