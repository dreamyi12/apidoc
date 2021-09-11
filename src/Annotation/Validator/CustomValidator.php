<?php

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Annotation\Validator;


use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class CustomValidator extends AbstractAnnotation
{
    /**
     * 收集名称
     * @var string
     */
    public $name;

}