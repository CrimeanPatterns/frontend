<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Test;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AdvtTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestTrackingPixel extends AbstractTemplate
{
    use AdvtTrait;

    public $accounts = [];

    public static function getDescription(): string
    {
        return 'Test message';
    }

    public static function getStatus(): int
    {
        return self::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        return new static();
    }
}
