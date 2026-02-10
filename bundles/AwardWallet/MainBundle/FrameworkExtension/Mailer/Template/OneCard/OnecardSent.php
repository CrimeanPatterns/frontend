<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\OneCard;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OnecardSent extends AbstractTemplate
{
    public static function getDescription(): string
    {
        return "OneCard order is ready for shipment";
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
