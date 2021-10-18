<?php

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Annotation\Abstracts;

use Dreamyi12\ApiDoc\Exception\ValidationException;
use Hyperf\Di\Annotation\AnnotationInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\Utils\Contracts\Arrayable;
use Dreamyi12\ApiDoc\Annotation\Collector\CustomCollector;

abstract class CustomAnnotation implements AnnotationInterface, Arrayable
{
    /**
     * @var string
     */
    protected $only = "name";

    /**
     * CustomAnnotation constructor.
     * @param ...$value
     * @throws \Exception
     */
    public function __construct(...$value)
    {
        $formattedValue = $this->formatParams($value);

        foreach ($formattedValue as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }

    }

    public function collectClass(string $className): void
    {
        //其中name为必须参数
        if (!isset($this->{$this->only}) || empty($this->{$this->only})) {
            throw new \Exception("The {$this->only} parameter must be defined to inherit a custom collector!");
        }
        //当前注解类 关键词 收集类
        CustomCollector::collectClass(static::class, $this->{$this->only}, $className);
    }

    public function collectMethod(string $className, ?string $target): void
    {
        //当前注解类 收集类 方法 当前对象
        CustomCollector::collectMethod(static::class, $className, $target, $this);
    }

    public function collectProperty(string $className, ?string $target): void
    {
        //当前注解类 收集类 方法 当前对象
        CustomCollector::collectMethod(static::class, $className, $target, $this);
    }

    public function toArray(): array
    {
        $properties = ReflectionManager::reflectClass(static::class)->getProperties(ReflectionProperty::IS_PUBLIC);
        $result = [];
        foreach ($properties as $property) {
            $result[$property->getName()] = $property->getValue($this);
        }
        return $result;
    }

    protected function formatParams($value): array
    {
        if (isset($value[0])) {
            $value = $value[0];
        }
        if (!is_array($value)) {
            $value = ['value' => $value];
        }
        return $value;
    }

}