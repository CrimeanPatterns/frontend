<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\AccountProperty;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class PropertyKind
{
    private int $kind;

    public function __construct(int $kind)
    {
        $this->kind = $kind;
    }

    public function getKind(): int
    {
        return $this->kind;
    }
}
