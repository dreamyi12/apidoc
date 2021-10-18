<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/6
 * Time: 16:11
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Annotation\Casts;

use Hyperf\Di\Annotation\AbstractAnnotation;
use Dreamyi12\ApiDoc\Annotation\Abstracts\CustomAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class CastsClass extends CustomAnnotation {

    /**
     * 收集名称
     * @var string
     */
    public $name;

}