<?php

namespace AwardWallet\MainBundle\Service\Lounge\Predictor;

interface ComparatorInterface
{
    public function compare(LoungeNormalized $lounge1, LoungeNormalized $lounge2): float;
}
