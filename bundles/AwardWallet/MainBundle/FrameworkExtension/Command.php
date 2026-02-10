<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;

class Command extends \Symfony\Component\Console\Command\Command
{
    public function __construct($name = null)
    {
        LocalizeService::defineDateTimeFormat();
        parent::__construct($name);
    }
}
