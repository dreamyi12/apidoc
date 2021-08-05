<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/24
 * Time: 17:36
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class File extends Params {

    public $in = 'formData';

}