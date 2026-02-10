<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\Entity\Coupon;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FreeUpgrade extends AbstractTemplate
{
    /**
     * @var Usr
     */
    public $invitee;

    /**
     * @var Coupon
     */
    public $coupon;

    public static function getDescription(): string
    {
        return "Free upgrade to AwardWallet Plus";
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($inviter = Tools::createUser());
        $template->invitee = Tools::createUser();
        $template->coupon = Tools::createCoupon($inviter);

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
