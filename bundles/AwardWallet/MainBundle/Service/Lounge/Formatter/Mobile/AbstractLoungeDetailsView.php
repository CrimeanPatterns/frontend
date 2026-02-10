<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

abstract class AbstractLoungeDetailsView extends AbstractBlockView
{
    public function __construct()
    {
        parent::__construct('loungeDetails');
    }
}
