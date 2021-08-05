<?php

namespace Dreamyi12\ApiDoc\Model;

use Dreamyi12\ApiDoc\Condition\ConditionHandle;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Utils\Str;

class BaseModel extends Model
{
    /**
     * @var array
     */
    protected $castsClass = [];

    /**
     * BaseModel constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($this->casts as $cast => $value) {
            if (!isset($attributes[$cast]) || empty($attributes[$cast])) {
                $attributes[$cast] = "value";
            }
        }
        parent::__construct($attributes);
    }

    /**
     * Additional model conditions
     *
     * @return \Hyperf\Database\Model\Builder
     */
    public static function condition()
    {
        return (new ConditionHandle(self::query()))->handle()->getBuilder();
    }

    /**
     * Set a given attribute on the model
     *
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        if ($value && $this->isDateAttribute($key)) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isClassCastable($key)) {
            $class = $this->parseCasterClass($this->getCasts()[$key]);
            $class_name = $this->castsClass[$class] . ":" . $this->cuttingCastsName($this->casts[$key]);
            $this->casts[$key] = $class_name;
            $this->setClassCastableAttribute($key, $value);

            return $this;
        }

        if ($this->isJsonCastable($key) && !is_null($value)) {
            $value = $this->castAttributeAsJson($key, $value);
        }

        // If this attribute contains a JSON ->, we'll set the proper value in the
        // attribute's underlying array. This takes care of properly nesting an
        // attribute in the array's value in the case of deeply nested items.
        if (Str::contains($key, '->')) {
            return $this->fillJsonAttribute($key, $value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Determine if the given key is cast using a custom class. Dream
     *
     * @param string $key
     * @return bool
     */
    protected function isClassCastable($key)
    {
        return array_key_exists($key, $this->getCasts())
            && key_exists($this->parseCasterClass($this->getCasts()[$key]), $this->castsClass);
    }

    /**
     * Gets the name of casts Dream
     * @param $key
     * @return mixed|string
     */
    protected function cuttingCastsName($key)
    {
        if (is_string($key) && strpos($key, ':') !== false) {
            [$segments, $arguments] = explode(':', $key, 2);
            return $arguments;
        }
        return $key;
    }
}