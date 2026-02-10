<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Classes;

interface ResolverInterface
{
    public function add(ConverterInterface $item);

    public function set(array $items);

    public function resolve();

    public function isEmpty();
}
