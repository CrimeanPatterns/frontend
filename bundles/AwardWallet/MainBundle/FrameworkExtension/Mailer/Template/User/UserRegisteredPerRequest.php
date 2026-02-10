<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserRegisteredPerRequest extends AbstractTemplate
{
    /**
     * @var Usr
     */
    public $invitee;

    /**
     * @var int
     */
    public $usersNeeded;

    public static function getDescription(): string
    {
        return "User registered per your request";
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->invitee = Tools::createUser();
        $template->usersNeeded = rand(1, 4);

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
