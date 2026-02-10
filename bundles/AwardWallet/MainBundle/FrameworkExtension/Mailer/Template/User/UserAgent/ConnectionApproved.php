<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConnectionApproved extends AbstractTemplate
{
    /**
     * @var Useragent
     */
    public $connection;

    public static function getDescription(): string
    {
        return 'Approved connection request';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($user = Tools::createUser());
        $template->connection = Tools::createConnection(Tools::createUser(), $user);

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
