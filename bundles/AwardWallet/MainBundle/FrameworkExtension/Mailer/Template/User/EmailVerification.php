<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailVerification extends AbstractTemplate
{
    public static function getDescription(): string
    {
        return 'Email verification request';
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
