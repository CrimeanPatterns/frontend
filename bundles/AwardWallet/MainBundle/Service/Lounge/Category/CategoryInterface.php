<?php

namespace AwardWallet\MainBundle\Service\Lounge\Category;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;

interface CategoryInterface
{
    public function match(LoungeInterface $lounge): bool;
}
