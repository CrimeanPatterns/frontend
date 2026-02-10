<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Twig\Replacement;

class ReplacementCollection
{
    private $collection = [];

    public function __construct(iterable $handlers)
    {
        $this->collection = $handlers;
    }

    public function getCollection()
    {
        return $this->collection;
    }
}
