<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\Entity\OneTimeCode;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Otc extends AbstractTemplate
{
    /**
     * @var OneTimeCode
     */
    public $code;

    /**
     * @var string
     */
    public $lastIp;

    /**
     * @var string
     */
    public $lastLocation;

    /**
     * @var string
     */
    public $currentIp;

    /**
     * @var string
     */
    public $currentLocation;

    public static function getDescription(): string
    {
        return "One Time Access Code";
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($user = Tools::createUser());

        $code = new OneTimeCode();
        $code->setUser($user);
        $template->code = $code;
        $template->lastIp = '74.193.78.90';
        $template->lastLocation = 'Pflugerville, TX, United States';
        $template->currentIp = '50.84.234.99';
        $template->currentLocation = 'United States';

        return $template;
    }
}
