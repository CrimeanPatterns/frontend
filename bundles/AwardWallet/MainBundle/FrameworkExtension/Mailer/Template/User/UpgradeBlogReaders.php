<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpgradeBlogReaders extends AbstractTemplate
{
    public int $blogpostCount = 1;

    public int $amountTimeSpent = 2;

    public string $untilDate = 'date';

    public static function getDescription(): string
    {
        return 'Complimentary Upgrade to AwardWallet Plus of heavy blog readers';
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->blogpostCount = 1;
        $template->amountTimeSpent = 2;
        $template->untilDate = date('F j, Y');

        return $template;
    }
}
