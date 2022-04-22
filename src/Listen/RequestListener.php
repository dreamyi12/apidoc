<?php

namespace Dreamyi12\ApiDoc\Listen;

use Hyperf\Framework\Event\BootApplication;

class RequestListener
{
    public function listen(): array
    {
        return [
            BootApplication::class
        ];
    }

}