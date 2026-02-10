<?php

namespace AwardWallet\MainBundle\Service\InAppPurchase;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class Product
{
    public string $id;

    public ?string $type;

    public ?string $description;

    public function __construct(string $id, ?string $type = null, ?string $description = null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->description = $description;
    }
}
