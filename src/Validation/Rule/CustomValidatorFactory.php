<?php


namespace Dreamyi12\ApiDoc\Validation\Rule;


use Hyperf\Contract\TranslatorInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;

abstract class CustomValidatorFactory implements CustomValidatorInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;


    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }


}