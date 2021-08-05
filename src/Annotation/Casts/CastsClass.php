<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/6
 * Time: 16:11
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Casts\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class CastsClass extends AbstractAnnotation {

    /**
     * 收集名称
     * @var string
     */
    public $name;

}