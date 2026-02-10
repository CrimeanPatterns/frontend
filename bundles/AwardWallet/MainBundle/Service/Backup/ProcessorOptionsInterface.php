<?php

namespace AwardWallet\MainBundle\Service\Backup;

use AwardWallet\MainBundle\Service\Backup\Model\ProcessorOptions;

interface ProcessorOptionsInterface
{
    public function registerOptions(ProcessorOptions $options): void;
}
