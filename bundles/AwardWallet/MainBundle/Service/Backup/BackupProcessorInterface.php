<?php

namespace AwardWallet\MainBundle\Service\Backup;

interface BackupProcessorInterface
{
    public function register(ProcessorInterestInterface $processorInterest): void;
}
