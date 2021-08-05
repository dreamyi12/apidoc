<?php
/**
 * Copyright (c) 2020 LKK All rights reserved
 * User: kakuilan
 * Date: 2020/3/9
 * Time: 15:44
 * Desc:
 */

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Form extends Params {

    public $in = 'formData';

}