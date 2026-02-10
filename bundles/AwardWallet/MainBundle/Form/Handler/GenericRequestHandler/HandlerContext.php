<?php

namespace AwardWallet\MainBundle\Form\Handler\GenericRequestHandler;

use Symfony\Component\HttpFoundation\ParameterBag;

class HandlerContext
{
    /**
     * @var ParameterBag
     */
    public $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = new ParameterBag($attributes);
    }
}
