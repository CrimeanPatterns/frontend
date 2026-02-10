<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailOffer extends AbstractTemplate
{
    public $layout;

    public $subject;

    public $logo;

    public $preview;

    public $body;

    public $head;

    public $style;

    public static function getDescription(): string
    {
        return 'Static offer';
    }

    public static function getStatus(): int
    {
        return self::STATUS_NOT_READY;
    }

    public static function createFake(ContainerInterface $container, $options = [])
    {
        return new static();
    }
}
