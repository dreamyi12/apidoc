<?php

namespace Dreamyi12\ApiDoc\Condition\Abstracts;

use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\Utils\ApplicationContext;
use Dreamyi12\ApiDoc\Condition\Condition;
use Dreamyi12\ApiDoc\Condition\Interfaces\ConditionInterface;
use Dreamyi12\ApiDoc\Condition\WhereParams;

abstract class ConditionAbstract implements ConditionInterface
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var Condition
     */
    protected $condition;

    /**
     * ConditionAbstract constructor.
     * @param Builder|Model $builder
     */
    public function __construct(Builder|Model $builder)
    {
        $this->builder = $builder;
    }

    /**
     *
     * @param null $where
     * @return ConditionInterface
     * @throws \Exception
     */
    public function handle($where = null): ConditionInterface
    {
        $where = !empty($where) ? $where : (new Condition())->getWhereParams();
        if (empty($where)) return $this;
        $with = [];
        $when = [];
        foreach ($where as $field => $option) {
            if (!method_exists($this, $option->getOp())) {
                throw new \Exception("The {$field} method does not exist");
            }
            if ($option->getValue() === null) {
                continue;
            }
            if ($option->getField()) {
                $field = $option->getField();
            }
            if ($option->getOp() === "has") {
                $relation = $option->getWith();
                $option->setOp($option->getType());
                $with[$relation][$field] = $option;
                continue;
            } else if ($option->getOp() === "when") {
                $option->setOp($option->getType());
                $when[$option->getKey()][$field] = $option;
                continue;
            }
            $option->setField($field);
            $this->builder = call_user_func(array($this, $option->getOp()), $option);
        }

        foreach ($with as $relation => $conditions) {
            $this->builder = $this->has($relation, $conditions);
        }

        foreach ($when as $conditions) {
            $this->builder = $this->when($conditions);
        }
        return $this;
    }

    /**
     * @param WhereParams $whereParams
     * @param $operator
     * @return Builder
     */
    public function where(WhereParams $whereParams, $operator)
    {
        return $this->builder->where($whereParams->getField(), $operator, $whereParams->getValue(), $whereParams->getMode());
    }


    /**
     * @return Builder
     */
    public function getBuilder()
    {
        return $this->builder;
    }
}