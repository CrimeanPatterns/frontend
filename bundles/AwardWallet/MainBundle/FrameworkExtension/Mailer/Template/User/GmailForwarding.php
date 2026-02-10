<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GmailForwarding extends AbstractTemplate
{
    /**
     * @var string
     */
    public $code;

    public static function getDescription(): string
    {
        return "Automatic forwarding approved";
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->code = rand(99999, 99999999);

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
