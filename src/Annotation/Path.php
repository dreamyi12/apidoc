<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/9
 * Time: 15:45
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Path extends Params {

    public $in = 'path';

}