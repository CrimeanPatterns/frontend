<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReauthCode extends AbstractTemplate
{
    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $ip;

    /**
     * @var string
     */
    public $location;

    public static function getDescription(): string
    {
        return 'Additional verification';
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->code = StringHandler::getRandomString(ord('0'), ord('9'), 6);
        $template->ip = '74.193.78.90';
        $template->location = 'Pflugerville, TX, United States';

        return $template;
    }
}
