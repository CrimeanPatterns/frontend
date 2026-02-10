<?php

namespace AwardWallet\WidgetBundle\Widget\Classes;

use AwardWallet\MainBundle\Entity\Usr;

interface UserWidgetInterface extends WidgetInterface
{
    /**
     * @return Usr
     */
    public function getCurrentUser();
}
