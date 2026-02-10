<?php

namespace AwardWallet\MainBundle\Timeline\Item;

class Layover extends AbstractLayover
{
    public function __construct(AbstractItinerary $left, AbstractItinerary $right)
    {
        $leftSource = $left->getSource();

        parent::__construct(
            $left,
            $right,
            sprintf('%s (%s)', $leftSource->getArrcode(), $leftSource->getArrAirportName(false))
        );
    }

    public function getType(): string
    {
        return Type::LAYOVER;
    }

    public function getIcon(): string
    {
        return Icon::BENCH;
    }
}
