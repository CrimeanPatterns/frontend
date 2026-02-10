<?php

namespace AwardWallet\MainBundle\Timeline\Item;

interface LayoverBoundaryInterface
{
    public const LAYOVER_TYPE_START = 1;
    public const LAYOVER_TYPE_END = 2;

    public function setLayoverBoundaryType(?int $type);

    public function isLayoverBoundaryType(int $type): bool;
}
