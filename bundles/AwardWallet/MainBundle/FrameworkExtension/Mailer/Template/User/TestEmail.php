<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\Controller\Profile\NotificationsController;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestEmail extends AbstractTemplate
{
    /**
     * @var string
     */
    public $message;

    public static function getDescription(): string
    {
        return "AwardWallet Test Email";
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());

        $template->message = NotificationsController::MESSAGES[array_rand(NotificationsController::MESSAGES)];

        return $template;
    }
}
