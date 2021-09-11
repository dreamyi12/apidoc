<?php


namespace Dreamyi12\ApiDoc\Validation\Rule;


use Hyperf\Contract\TranslatorInterface;

abstract class CustomValidatorFactory implements CustomValidatorInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }


}