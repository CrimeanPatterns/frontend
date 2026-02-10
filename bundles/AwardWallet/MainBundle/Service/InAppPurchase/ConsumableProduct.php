<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class ConsumableProduct extends Product
{
    public int $count;

    public function __construct(string $id, int $count)
    {
        parent::__construct($id);

        $this->count = $count;
    }
}
