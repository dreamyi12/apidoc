<?php

namespace Dreamyi12\ApiDoc\Validation\Rule;

interface CustomValidatorInterface
{
    /**
     * @param array $data
     * @param $value
     * @param string $field
     * @param string $filed_name
     * @param array $options
     * @return array
     */
    public function handle(array $data, $value, string $field, string $filed_name, array $options = []): array;

}