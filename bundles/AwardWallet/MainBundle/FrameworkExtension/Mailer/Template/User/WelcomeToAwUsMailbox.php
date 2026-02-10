<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WelcomeToAwUsMailbox extends AbstractTemplate
{
    public $lang = 'en';

    public $locale = 'en_US';

    public static function getDescription(): string
    {
        return 'Welcome to AwardWallet (after registration), for US user, mailbox intro';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        return new static(Tools::createUser());
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
