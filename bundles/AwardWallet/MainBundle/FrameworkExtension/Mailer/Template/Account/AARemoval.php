<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account;

use AwardWallet\MainBundle\Command\Account\AARemovalCommand;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AARemoval extends AbstractTemplate
{
    /** @var array */
    public $accounts;

    public static function getDescription(): string
    {
        return 'American Airlines Forces AwardWallet to Stop Tracking AA Accounts';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->accounts = [];
        $template->isTripsFound = true;
        $template->changeOrgLink = AARemovalCommand::CHANGE_ORG_LINK;
        $template->helloName = 'FirstName';

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
