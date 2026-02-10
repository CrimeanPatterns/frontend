<?php

namespace AwardWallet\MainBundle\Timeline\Item;

trait LayoverBoundaryTrait
{
    protected ?int $layoverBoundaryType = null;

    public function setLayoverBoundaryType(?int $type)
    {
        if (is_null($type)) {
            $this->layoverBoundaryType = null;
        } else {
            $this->layoverBoundaryType |= $type;
        }
    }

    public function isLayoverBoundaryType(int $type): bool
    {
        return boolval($type & $this->layoverBoundaryType);
    }
}
