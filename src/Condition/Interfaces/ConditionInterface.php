<?php

namespace Dreamyi12\ApiDoc\Condition\Interfaces;

use Closure;
use Hyperf\Database\Model\Builder;
use Dreamyi12\ApiDoc\Condition\WhereParams;

interface ConditionInterface
{

    public function eq(WhereParams $whereParams): Builder;

    public function neq(WhereParams $whereParams): Builder;

    public function gt(WhereParams $whereParams): Builder;

    public function egt(WhereParams $whereParams): Builder;

    public function lt(WhereParams $whereParams): Builder;

    public function elt(WhereParams $whereParams): Builder;

    public function likeLeft(WhereParams $whereParams): Builder;

    public function likeRight(WhereParams $whereParams): Builder;

    public function between(WhereParams $whereParams): Builder;

    public function notBetween(WhereParams $whereParams): Builder;

    public function in(WhereParams $whereParams): Builder;

    public function notIn(WhereParams $whereParams): Builder;

    public function has($relation, $conditions): Builder;

    public function when($conditions): Builder;

}