<?php


namespace Dreamyi12\ApiDoc\Condition;


use Hyperf\Database\Model\Builder;
use Dreamyi12\ApiDoc\Condition\Abstracts\ConditionAbstract;

class ConditionHandle extends ConditionAbstract
{
    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function eq(WhereParams $whereParams): Builder
    {
        return $this->where($whereParams,"=");
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function neq(WhereParams $whereParams): Builder
    {
        return $this->where($whereParams,"!=");
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function gt(WhereParams $whereParams): Builder
    {
        return $this->where($whereParams,">");
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function egt(WhereParams $whereParams): Builder
    {
        return $this->where($whereParams,">=");
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function lt(WhereParams $whereParams): Builder
    {
        return $this->where($whereParams,"<");
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function elt(WhereParams $whereParams): Builder
    {
        return $this->where($whereParams,"<=");
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function likeLeft(WhereParams $whereParams): Builder
    {
        return $this->builder->where($whereParams->getField(), "like", "%".$whereParams->getValue(), $whereParams->getMode());
    }


    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function likeRight(WhereParams $whereParams): Builder
    {
        return $this->builder->where($whereParams->getField(), "like", $whereParams->getValue()."%", $whereParams->getMode());
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function like(WhereParams $whereParams): Builder
    {
        return $this->builder->where($whereParams->getField(), "like", "%".$whereParams->getValue()."%", $whereParams->getMode());
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function between(WhereParams $whereParams): Builder
    {
        $value = is_array($whereParams->getValue()) ? $whereParams->getValue() : explode($whereParams->getSymbol(), $whereParams->getValue());
        return $this->builder->whereBetween($whereParams->getField(), $value, $whereParams->getMode());
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function notBetween(WhereParams $whereParams): Builder
    {
        $value = is_array($whereParams->getValue()) ? $whereParams->getValue() : explode($whereParams->getSymbol(), $whereParams->getValue());
        return $this->builder->whereNotBetween($whereParams->getField(), $value, $whereParams->getMode());
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function in(WhereParams $whereParams): Builder
    {
        $value = is_array($whereParams->getValue()) ? $whereParams->getValue() : explode($whereParams->getSymbol(), $whereParams->getValue());
        return $this->builder->whereIn($whereParams->getField(), $value, $whereParams->getMode());
    }

    /**
     * @param WhereParams $whereParams
     * @return Builder
     */
    public function notIn(WhereParams $whereParams): Builder
    {
        $value = is_array($whereParams->getValue()) ? $whereParams->getValue() : explode($whereParams->getSymbol(), $whereParams->getValue());
        return $this->builder->whereNotIn($whereParams->getField(), $value, $whereParams->getMode());
    }

    /**
     * @param $relation
     * @param $conditions
     * @return Builder
     */
    public function has($relation, $conditions): Builder
    {
        return $this->builder->whereHas($relation, function ($query) use ($conditions) {
            $condition = new ConditionHandle($query);
            $condition->handle($conditions)->getBuilder();
        });
    }

    /**
     * @param $conditions
     * @return Builder
     * @throws \Exception
     */
    public function when($conditions): Builder
    {
        return $this->builder->where(function ($query) use ($conditions) {
            $condition = new ConditionHandle($query);
            $condition->handle($conditions)->getBuilder();
        });
    }
}