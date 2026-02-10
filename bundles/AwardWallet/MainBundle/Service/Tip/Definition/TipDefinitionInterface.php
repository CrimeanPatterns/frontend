<?php

namespace AwardWallet\MainBundle\Service\Tip\Definition;

use AwardWallet\MainBundle\Entity\Usr;

interface TipDefinitionInterface
{
    public function show(Usr $user, string $routeName): ?bool;

    public function getElementId(): string;
}
