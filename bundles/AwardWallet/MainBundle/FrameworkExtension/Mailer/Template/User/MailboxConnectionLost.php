<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MailboxConnectionLost extends AbstractTemplate
{
    public ?int $mailboxId = null;
    public ?string $mailboxEmail = null;
    public ?string $link = null;

    public static function getDescription(): string
    {
        return 'Mailbox Connection Lost';
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->mailboxId = 123;
        $template->mailboxEmail = 'some@mailbox.com';
        $template->link = 'https://awardwallet.com/mailboxes/';

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
