<?php


namespace Dreamyi12\ApiDoc\Condition;

/**
 * Class WhereParams
 * @package Dreamyi12\ApiDoc\Condition
 */
class WhereParams
{
    /**
     * condition
     * @var string
     */
    private $op;

    /**
     * relation
     * @var
     */
    private $with;

    /**
     * @var
     */
    private $type;

    /**
     * mode
     * @var string
     */
    private $mode = "AND";

    /**
     * symbol
     * @var string
     */
    private $symbol;

    /**
     *
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $key = "v1";

    /**
     * @var string
     */
    private $function;

    /**
     * @var
     */
    private $value;

    /**
     * @return mixed
     */
    public function getOp()
    {
        return $this->op;
    }

    /**
     * @param mixed $op
     */
    public function setOp($op): void
    {
        $this->op = $op;
    }

    /**
     * @return mixed
     */
    public function getWith()
    {
        return $this->with;
    }

    /**
     * @param mixed $with
     */
    public function setWith($with): void
    {
        $this->with = $with;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param mixed $mode
     */
    public function setMode($mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @return mixed
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @param mixed $symbol
     */
    public function setSymbol($symbol): void
    {
        $this->symbol = $symbol;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key): void
    {
        $this->key = $key;
    }


    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param mixed $field
     */
    public function setField($field): void
    {
        $this->field = $field;
    }

    /**
     * @return string|null
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @param string $function
     */
    public function setFunction(string $function): void
    {
        $this->function = $function;
    }


}