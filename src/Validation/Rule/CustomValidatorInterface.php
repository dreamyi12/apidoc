<?php

namespace Dreamyi12\ApiDoc\Validation\Rule;

interface CustomValidatorInterface
{

    public function handle($data, $value, $filed_name, $options = []): array;

}