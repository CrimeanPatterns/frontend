<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\UserAgent;

use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

class ConnectionRequest extends AbstractTemplate
{
    /**
     * @var Usr
     */
    public $inviter;

    /**
     * @var Invitecode
     */
    public $inviteCode;

    /**
     * @var Invites
     */
    public $invite;

    public static function getDescription(): string
    {
        return "Personal connection request (inviting user)";
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        return $builder;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static($email = "test@test.com");

        $template->inviter = Tools::getDefaultMerchant($container)->getUserID();
        $template->inviteCode = Tools::createInviteCode($template->inviter)->setEmail($email);
        $template->invite = Tools::createInvites(
            $template->inviteCode->getUserid(),
            $template->inviteCode->getEmail(),
            $template->inviteCode->getCode()
        );

        return $template;
    }
}
