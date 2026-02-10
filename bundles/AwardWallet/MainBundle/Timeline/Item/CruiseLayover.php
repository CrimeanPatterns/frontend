<?php

namespace AwardWallet\MainBundle\Timeline\Item;

class CruiseLayover extends AbstractLayover
{
    public function __construct(AbstractItinerary $left, AbstractItinerary $right)
    {
        $leftSource = $left->getSource();

        parent::__construct(
            $left,
            $right,
            $leftSource->getArrname()
        );
    }

    public function getType(): string
    {
        return Type::LAYOVER_CRUISE;
    }

    public function getIcon(): string
    {
        return Icon::PALM;
    }
}
