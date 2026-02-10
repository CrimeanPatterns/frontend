<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailChanged extends AbstractTemplate
{
    public static function getDescription(): string
    {
        return 'User email changed';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->emailFrom = 'email@from';
        $template->emailTo = 'email@to';

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
