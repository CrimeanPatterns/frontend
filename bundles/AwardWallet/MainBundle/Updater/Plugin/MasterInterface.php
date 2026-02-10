<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Updater\Event\AbstractEvent;
use AwardWallet\MainBundle\Updater\InternalOptions;
use AwardWallet\MainBundle\Updater\Option;

interface MasterInterface
{
    public function addEvent(AbstractEvent $event);

    public function removeAccount(Account $account);

    /**
     * @param string $message
     */
    public function log(?Account $account = null, $message = '');

    /**
     * @param Option::*|InternalOptions::* $option
     * @throws \UnexpectedValueException
     */
    public function getOption(string $option, $defaultValue = null);

    /**
     * @param Option::*|InternalOptions::* $option
     */
    public function setOption(string $option, $value): self;

    public function getKey(): string;
}
