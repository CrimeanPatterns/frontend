<?php

namespace AwardWallet\MainBundle\Globals\Cart;

interface AwPlusUpgradableInterface
{
    /**
     * returns strtotime representation of AwPlus upgrade duration, e.g. "+1 year".
     *
     * @return string
     */
    public function getDuration();
}
