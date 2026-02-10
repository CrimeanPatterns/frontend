<?php

namespace AwardWallet\MainBundle\Updater\Formatter;

use AwardWallet\MainBundle\Updater\Plugin\MasterInterface;

interface FormatterInterface
{
    public function format(array $events);

    /**
     * @return $this
     */
    public function setMaster(MasterInterface $master);
}
