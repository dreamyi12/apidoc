<?php

declare(strict_types=1);

namespace Dreamyi12\ApiDoc\Annotation\Validator;


use Dreamyi12\ApiDoc\Annotation\Abstracts\CustomAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class CustomValidator extends CustomAnnotation
{
    /**
     * 收集名称
     * @var string
     */
    public $name;

}